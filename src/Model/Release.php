<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Model;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\Data\Cacheable;
use ParagonIE\Herd\Exception\EmptyValueException;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;

/**
 * Class Release
 *
 * This class encapsulates an update to a product.
 *
 * @package ParagonIE\Herd
 */
class Release implements Cacheable
{
    /** @var string $data */
    protected $data;

    /** @var Product $product */
    protected $product;

    /** @var string $signature */
    protected $signature;

    /** @var string $version */
    protected $version;

    /** @var string $summaryHash */
    protected $summaryHash;

    /**
     * Release constructor.
     *
     * @param Product $product
     * @param string $version
     * @param string $data
     * @param string $signature
     * @param string $summaryHash
     */
    public function __construct(
        Product $product,
        string $version,
        string $data,
        string $signature,
        string $summaryHash
    ) {
        $this->product = $product;
        $this->version = $version;
        $this->data = $data;
        $this->signature = $signature;
        $this->summaryHash = $summaryHash;
    }

    /**
     * @param EasyDB $db
     * @param int $id
     * @return self
     * @throws EmptyValueException
     */
    public static function byId(EasyDB $db, int $id): self
    {
        /** @var array<string, string> $r */
        $r = $db->row('SELECT * FROM herd_product_updates WHERE id = ?', $id);
        if (empty($r)) {
            throw new EmptyValueException('Could not find this software release');
        }
        return new static(
            Product::byId($db, (int) $r['product']),
            $r['version'],
            $r['data'],
            $r['signature'],
            $r['summaryhash']
        );
    }

    /**
     * @return bool
     */
    public function signatureValid(): bool
    {
        /** @var array<int, SigningPublicKey> $publicKeys */
        $publicKeys = $this->getPublicKeys();
        foreach ($publicKeys as $pKey) {
            /** @var SigningPublicKey $pKey */
            if (\sodium_crypto_sign_verify_detached(
                $this->signature,
                $this->data,
                $pKey->getString(true)
            )) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return array<int, SigningPublicKey>
     */
    public function getPublicKeys(): array
    {
        return $this
            ->product
            ->getVendor()
            ->getPublicKeys();
    }

    /**
     * @return string
     */
    public function getSummaryHash(): string
    {
        return $this->summaryHash;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this
            ->product
            ->getVendor();
    }
}
