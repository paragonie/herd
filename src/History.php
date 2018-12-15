<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\Certainty\Exception\BundleException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\Data\Remote;
use ParagonIE\Herd\Model\{
    Product,
    Vendor
};
use ParagonIE\Herd\Exception\{
    ChronicleException,
    EmptyValueException,
    InvalidOperationException
};
use ParagonIE\Sapient\Sapient;

/**
 * Class History
 *
 * Fetches updates from the remote chronicles and stores new records
 * in the local database
 *
 * @package ParagonIE\Herd
 */
class History
{
    /** @var Herd $herd */
    protected $herd;

    /**
     * History constructor.
     * @param Herd $herd
     */
    public function __construct(Herd $herd)
    {
        $this->herd = $herd;
    }

    /**
     * @return EasyDB
     */
    public function getDatabase(): EasyDB
    {
        return $this->herd->getDatabase();
    }

    /**
     * Copy from the remote Chronicles onto the local history.
     *
     * @param bool $useTransaction
     * @return bool
     * @throws BundleException
     * @throws ChronicleException
     * @throws EmptyValueException
     */
    public function transcribe(bool $useTransaction = true): bool
    {
        $remote = $this->herd->selectRemote(true);
        /** @var array<int, array<string, string>> $updates */
        $updates = $this->herd->getUpdatesSince('', $remote);
        if (!$updates) {
            return true;
        }
        $db = $this->herd->getDatabase();

        // Don't wrap this in a transaction unless told to:
        if ($useTransaction) {
            $db->beginTransaction();
        }
        $inserts = 0;
        /** @var array<string, string> $last */
        $last = $db->row("SELECT * FROM herd_history ORDER BY id DESC LIMIT 1");
        if (empty($last)) {
            $last = [];
        }
        foreach ($updates as $up) {
            /** @var array<string, string> $up */
            if (!isset(
                $up['contents'],
                $up['prev'],
                $up['hash'],
                $up['summary'],
                $up['created'],
                $up['publickey'],
                $up['signature']
            )) {
                continue;
            }
            if ($this->isValidNextEntry($up, $last)) {
                if (!$this->quorumAgrees($remote, $up['summary'], $up['hash'])) {
                    continue;
                }
                $db->insert(
                    'herd_history',
                    [
                        'contents' => $up['contents'],
                        'prevhash' => $up['prev'],
                        'hash' => $up['hash'],
                        'summaryhash' => $up['summary'],
                        'publickey' => $up['publickey'],
                        'created' => $up['created'],
                        'signature' => $up['signature']
                    ]
                );
                /** @var int $historyID */
                $historyID = $db->cell(
                    "SELECT id FROM herd_history WHERE summaryhash = ?",
                    $up['summary']
                );
                try {
                    $this->parseContentsAndInsert($up['contents'], (int) $historyID, $up['summary']);
                } catch (\Throwable $ex) {
                    $this->markAccepted((int) $historyID);
                }
                ++$inserts;
                /** @var array<string, string> $last */
                $last = $up;
            }
        }
        if ($this->herd->getConfig()->getMinimalHistory()) {
            $this->pruneHistory();
        }
        if ($inserts === 0) {
            // This should not be rolled back unless told to:
            if ($useTransaction) {
                $db->rollBack();
            }
            return false;
        }
        // This should not be committed unless $useTransaction is TRUE:
        if ($useTransaction) {
            return $db->commit();
        }
        return true;
    }

    /**
     * @param array<string, string> $up
     * @param array<string, string> $prev
     * @return bool
     */
    public function isValidNextEntry(array $up, array $prev = []): bool
    {
        if (!empty($prev)) {
            if (!\hash_equals($up['prev'], $prev['hash'])) {
                // This does not follow from the previous hash.
                return false;
            }
        }

        /** @var string $publicKey */
        $publicKey = (string) Base64UrlSafe::decode($up['publickey']);
        /** @var string $signature */
        $signature = (string) Base64UrlSafe::decode($up['signature']);

        if (!\sodium_crypto_sign_verify_detached($signature, $up['contents'], $publicKey)) {
            // Invalid signature!
            return false;
        }

        /** @var string $prevHash */
        if (empty($up['prev'])) {
            $prevHash = '';
        } else {
            $prevHash = (string) Base64UrlSafe::decode($up['prev']);
        }
        /** @var string $hash */
        $hash = (string) Base64UrlSafe::decode($up['hash']);

        /** @var string $calcHash */
        $calcHash = \sodium_crypto_generichash(
            $up['created'] . $publicKey . $signature . $up['contents'],
            $prevHash
        );

        if (!\hash_equals($calcHash, $hash)) {
            // Hash did not match
            return false;
        }

        // All checks pass!
        return true;
    }

    /**
     * @param string $contents
     * @param int $historyID
     * @param string $hash
     * @param bool $override
     *
     * @return void
     * @throws EmptyValueException
     * @throws InvalidOperationException
     * @throws \Exception
     */
    public function parseContentsAndInsert(
        string $contents,
        int $historyID,
        string $hash,
        bool $override = false
    ) {
        /** @var array<string, string> $decoded */
        $decoded = \json_decode($contents, true);
        if (!\is_array($decoded)) {
            // Not a JSON message.
            $this->markAccepted($historyID);
            return;
        }
        if (!isset(
            $decoded['op'],
            $decoded['op-body'],
            $decoded['op-sig'],
            $decoded['op-public-key']
        )) {
            // Not a JSON message for our usage
            $this->markAccepted($historyID);
            return;
        }
        switch ($decoded['op']) {
            case 'add-key':
                $this->addPublicKey($decoded, $historyID, $hash, $override);
                break;
            case 'revoke-key':
                $this->revokePublicKey($decoded, $historyID, $hash, $override);
                break;
            case 'update':
                $this->registerUpdate($decoded, $historyID, $hash);
                break;
            default:
                // Unknown or unsupported operation.
                $this->markAccepted($historyID);
                return;
        }
    }

    /**
     * Irrelevant junk just gets marked as "accepted" so we don't prompt later
     *
     * @param int $historyID
     * @return void
     */
    protected function markAccepted(int $historyID)
    {
        $this->herd->getDatabase()->update(
            'herd_history',
            ['accepted' => true],
            ['id' => $historyID]
        );
    }

    /**
     * This creates a new public key for a vendor. If the vendor does not
     * exist, they will be created.
     *
     * @param array<string, string> $data
     * @param int $historyID
     * @param string $hash
     * @param bool $override
     * @return void
     * @throws EmptyValueException
     * @throws \Exception
     */
    protected function addPublicKey(
        array $data,
        int $historyID,
        string $hash,
        bool $override = false
    ) {
        try {
            $this->validateMessage($data, 'add-key');
        } catch (ChronicleException $ex) {
            return;
        }

        /** @var array<string, string> $opBody */
        $opBody = \json_decode($data['op-body'], true);
        if (!\is_array($opBody)) {
            return;
        }
        $db = $this->herd->getDatabase();

        try {
            /** @var int $vendorID */
            $vendorID = Vendor::getVendorID($db, $opBody['vendor']);
        } catch (EmptyValueException $ex) {
            // Creating a new vendor!
            /** @var int $vendorID */
            $vendorID = (int) $db->insertGet(
                'herd_vendors',
                [
                    'name' => $opBody['vendor'],
                    'created' => (new \DateTime())->format(\DateTime::ATOM),
                    'modified' => (new \DateTime())->format(\DateTime::ATOM)
                ],
                'id'
            );
        }
        $proceed = false;
        try {
            Vendor::keySearch($db, $vendorID, $data['op-public-key']);
            $proceed = true;
        } catch (EmptyValueException $ex) {
            $config = $this->herd->getConfig();
            if ($config->allowCoreToManageKeys()) {
                $coreVendorID = Vendor::getVendorID($db, $config->getCoreVendorName());

                // We don't catch this one:
                Vendor::keySearch($db, $coreVendorID, $data['op-public-key']);

                // Only proceed if we're allowed
                $proceed = $config->allowNonInteractiveKeyManagement();
            }
        }

        if (!empty($proceed) || $override) {
            // Insert the vendor key:
            $db->insert(
                'herd_vendor_keys',
                [
                    'history_create' => $historyID,
                    'summaryhash_create' => $hash,
                    'trusted' => true,
                    'vendor' => $vendorID,
                    'name' => $opBody['name']
                            ??
                        $db->cell(
                            'SELECT summaryhash FROM herd_history WHERE id = ?',
                            $historyID
                        ),
                    'publickey' => $opBody['publickey'],
                    'created' => (new \DateTime())->format(\DateTime::ATOM),
                    'modified' => (new \DateTime())->format(\DateTime::ATOM)
                ]
            );
            $this->markAccepted($historyID);
        }
    }

    /**
     * This revokes an existing public key for a given vendor.
     *
     * @param array<string, string> $data
     * @param int $historyID
     * @param string $hash
     * @param bool $override
     * @return void
     * @throws EmptyValueException
     * @throws InvalidOperationException
     */
    protected function revokePublicKey(
        array $data,
        int $historyID,
        string $hash,
        bool $override = false
    ) {
        try {
            $this->validateMessage($data, 'revoke-key');
        } catch (ChronicleException $ex) {
            return;
        }

        /** @var array<string, string> $opBody */
        $opBody = \json_decode($data['op-body'], true);
        if (!\is_array($opBody)) {
            return;
        }
        $config = $this->herd->getConfig();
        if (\hash_equals($data['op-public-key'], $opBody['publickey'])) {
            throw new InvalidOperationException(
                'You cannot revoke your own key. You must provide a new one first.'
            );
        }

        $db = $this->herd->getDatabase();

        $vendorID = Vendor::getVendorID($db, $opBody['vendor']);
        // Do not catch:
        $targetKeyID = Vendor::keySearch($db, $vendorID, $opBody['publickey']);

        $proceed = false;
        try {
            Vendor::keySearch($db, $vendorID, $data['op-public-key']);
            $proceed = true;
        } catch (EmptyValueException $ex) {
            if ($config->allowCoreToManageKeys()) {
                $coreVendorID = Vendor::getVendorID($db, $config->getCoreVendorName());

                // We don't catch this one:
                Vendor::keySearch($db, $coreVendorID, $data['op-public-key']);

                // Only proceed if we're allowed
                $proceed = $config->allowNonInteractiveKeyManagement();
            }
        }
        if (!empty($proceed) || $override) {
            // Revoke the vendor key:
            $db->update(
                'herd_vendor_keys',
                [
                    'history_revoke' => $historyID,
                    'summaryhash_revoke' => $hash,
                    'trusted' => false,
                    'modified' => (new \DateTime())->format(\DateTime::ATOM)
                ],
                [
                    'id' => $targetKeyID
                ]
            );
            $this->markAccepted($historyID);
        }
    }

    /**
     * This registers metadata about a new software update into the local
     * database.
     *
     * @param array<string, string> $data
     * @param int $historyID
     * @param string $hash
     * @return void
     * @throws EmptyValueException
     * @throws \Exception
     */
    protected function registerUpdate(array $data, int $historyID, string $hash)
    {
        try {
            $this->validateMessage($data, 'update');
        } catch (ChronicleException $ex) {
            return;
        }

        /** @var array<string, string> $opBody */
        $opBody = \json_decode($data['op-body'], true);
        if (!\is_array($opBody)) {
            return;
        }
        if (!isset(
            $opBody['vendor'],
            $opBody['name'],
            $opBody['version'],
            $opBody['metadata']
        )) {
            throw new EmptyValueException('Incomplete data packet.');
        }

        $db = $this->herd->getDatabase();

        $vendorID = Vendor::getVendorID($db, $opBody['vendor']);
        $publicKeyID = Vendor::keySearch($db, $vendorID, $data['op-public-key']);
        $productID = Product::upsert($db, $vendorID, $opBody['name']);

        $db->insert(
            'herd_product_updates',
            [
                'history' => $historyID,
                'summaryhash' => $hash,
                'version' => $opBody['version'],
                'body' => $data['op-body'],
                'product' => $productID,
                'publickey' => $publicKeyID,
                'signature' => $data['op-sig'],
                'created' => (new \DateTime())
                    ->format(\DateTime::ATOM),
                'modified' => (new \DateTime())
                    ->format(\DateTime::ATOM)
            ]
        );
        $this->markAccepted($historyID);
    }

    /**
     * Data/signature validation for an incoming message.
     *
     * @param array<string, string> $data
     * @param string $operation
     * @return void
     * @throws ChronicleException
     */
    protected function validateMessage(array $data, string $operation)
    {
        if (!isset(
            $data['op'],
            $data['op-body'],
            $data['op-sig'],
            $data['op-public-key']
        )) {
            throw new ChronicleException('Not a JSON message for our usage');
        }
        if (!\hash_equals($operation, $data['op'])) {
            throw new ChronicleException('Invalid operation');
        }

        /** @var string $publicKey */
        $publicKey = (string) Base64UrlSafe::decode($data['op-public-key']);
        /** @var string $signature */
        $signature = (string) Base64UrlSafe::decode($data['op-sig']);
        if (!\sodium_crypto_sign_verify_detached(
            $signature,
            $data['op-body'],
            $publicKey
        )) {
            throw new ChronicleException('Invalid signature');
        }
    }

    /**
     * Do the other Remotes allow us to establish a quorum (number of alternative
     * Remotes that must agree on the existence of this summary hash)?
     *
     * @param Remote $used
     * @param string $summary
     * @param string $currHash
     * @return bool
     * @throws \RangeException
     */
    protected function quorumAgrees(
        Remote $used,
        string $summary,
        string $currHash
    ): bool {
        $config = $this->herd->getConfig();
        $quorum = $config->getQuorum();
        if (empty($quorum)) {
            return true;
        }
        $sapient = new Sapient();

        /** @var array<int, Remote> $remotes */
        $remotes = $this->herd->getAllRemotes();

        // Remove duplicates
        foreach ($remotes as $i => $r) {
            /** @var Remote $r */
            if ($used->getUrl() === $r->getUrl()) {
                unset($remotes[$i]);
            }
        }

        if ($quorum > \count($remotes)) {
            // Prevent always-returning-false conditions:
            throw new \RangeException(
                'Quorum threshold is larger than available Remote pool'
            );
        }

        // As long as we have remotes left to query and have yet to establish quorum,
        // keep querying other remote Chronicles.
        $r = 0;
        while ($quorum > 0 && !empty($remotes)) {
            // Select one at random:
            try {
                $r = \random_int(1, \count($remotes)) - 1;
                /** @var Remote $remote */
                $remote = $remotes[$r];
                $decoded = $sapient->decodeSignedJsonResponse(
                    $remote->lookup($summary),
                    $remote->getPublicKey()
                );
                if ($decoded['status'] === 'OK') {
                    // Response was OK. Now let's search the responses for our currhash.
                    $match = false;
                    /** @var array<string, string> $res */
                    foreach ($decoded['results'] as $res) {
                        if (\hash_equals($currHash, $res['currhash'])) {
                            $match = true;
                            break;
                        }
                    }
                    if ($match) {
                        // This Remote source sees the same summary hash.
                        --$quorum;
                    }
                }
            } catch (\Throwable $ex) {
                // Probably a transfer exception. Move on.
            }
            unset($remotes[$r]);
            // Reset keys:
            $remotes = \array_values($remotes);
        }

        // If we have met quorum, return TRUE.
        // If we have yet to meet quorum, return FALSE.
        return $quorum < 1;
    }

    /**
     * Delete everything non-essential from the local database.
     *
     * This leaves only:
     *
     * - Non-accepted history entries
     * - The most recent entry
     *
     * @return void
     */
    protected function pruneHistory()
    {
        $db = $this->herd->getDatabase();
        /** @var string $historyID */
        $historyID = $db->cell("SELECT MAX(id) FROM herd_history");
        if (empty($historyID)) {
            return;
        }
        if ($db->getDriver() === 'sqlite') {
            $db->query("DELETE FROM herd_history WHERE accepted = 0 AND id < ?", $historyID);
        } else {
            $db->query("DELETE FROM herd_history WHERE NOT accepted AND id < ?", $historyID);
        }
    }
}
