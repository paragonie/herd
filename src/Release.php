<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\Herd\Data\Cacheable;
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

    /** @var HistoryRecord $history */
    protected $history;

    /** @var Product $product */
    protected $product;

    /** @var string */
    protected $signature;

    /** @var string */
    protected $version;

    /**
     * Release constructor.
     *
     * @param Product $product
     * @param HistoryRecord $record
     * @param string $version
     * @param string $data
     * @param string $signature
     */
    public function __construct(
        Product $product,
        HistoryRecord $record,
        string $version,
        string $data,
        string $signature
    ) {
        $this->history = $record;
        $this->product = $product;
        $this->version = $version;
        $this->data = $data;
        $this->signature = $signature;
    }

    /**
     * @return bool
     */
    public function signatureValid(): bool
    {
        /** @var array<int, SigningPublicKey> $publicKeys */
        $publicKeys = $this->getPublicKeys();
        foreach ($publicKeys as $pkey) {
            /** @var SigningPublicKey $pkey */
            if (\sodium_crypto_sign_verify_detached(
                $this->signature,
                $this->data,
                $pkey->getString(true)
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
        return $this->product->getVendor()->getPublicKeys();
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->product->getVendor();
    }
}
