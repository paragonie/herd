<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\Data\Cacheable;
use ParagonIE\Herd\Exception\EmptyValueException;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;

/**
 * Class Vendor
 * @package ParagonIE\Herd
 */
class Vendor implements Cacheable
{
    /** @var string $name */
    protected $name;

    /** @var array<int, SigningPublicKey> $publicKeys */
    protected $publicKeys = [];

    /**
     * Vendor constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param SigningPublicKey $publicKey
     * @return self
     */
    public function appendPublicKey(SigningPublicKey $publicKey): self
    {
        $this->publicKeys[] = $publicKey;
        return $this;
    }

    /**
     * @return array<int, SigningPublicKey>
     */
    public function getPublicKeys(): array
    {
        return $this->publicKeys;
    }

    /**
     * @param EasyDB $db
     * @param string $name
     * @return int
     * @throws EmptyValueException
     */
    public static function getVendorID(EasyDB $db, string $name = ''): int
    {
        /** @var int $id */
        $id = $db->cell("SELECT id FROM herd_vendors WHERE name = ?", $name);
        if (!$id) {
            throw new EmptyValueException('No vendor found for this name');
        }
        return (int) $id;
    }

    /**
     * @param EasyDB $db
     * @param int $vendorID
     * @param string $publicKey
     * @return int
     * @throws EmptyValueException
     */
    public static function keySearch(EasyDB $db, int $vendorID, string $publicKey): int
    {
        /** @var array<mixed, array<string, string>> $vendorKeys */
        $vendorKeys = $db->run(
            "SELECT * FROM herd_vendor_keys WHERE trusted AND vendor = ?",
            $vendorID
        );
        foreach ($vendorKeys as $vk) {
            /** @var array<string, string> $vk */
            if (\hash_equals($publicKey, $vk['publickey'])) {
                return (int) $vk['id'];
            }
        }
        throw new EmptyValueException('Public key not found for this vendor');
    }
}
