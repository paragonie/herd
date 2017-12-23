<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Data;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\{
    Config,
    HistoryRecord,
    Product,
    Release,
    Vendor
};
use ParagonIE\Herd\Exception\{
    EmptyValueException,
    EncodingError,
    FilesystemException
};
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;

/**
 * Class Local
 *
 * Encapsulates a local database.
 *
 * @package ParagonIE\Herd\Data
 */
class Local
{
    /** @var EasyDB */
    protected $ezdb;

    /**
     * Local constructor.
     * @param EasyDB $db
     */
    public function __construct(EasyDB $db)
    {
        $this->ezdb = $db;
    }

    /**
     * @return EasyDB
     */
    public function getDatabase(): EasyDB
    {
        return $this->ezdb;
    }

    /**
     * @param string $path
     * @return Config
     * @throws EncodingError
     * @throws FilesystemException
     */
    public function loadConfigFile(string $path = ''): Config
    {
        if (!$path) {
            $path = dirname(dirname(__DIR__)) . '/data/config.json';
        }
        return Config::fromFile($path);
    }


    /**
     * @param int $id
     * @return HistoryRecord
     * @throws EmptyValueException
     */
    public function loadHistory(int $id): HistoryRecord
    {
        try {
            $cached = ObjectCache::get('history', $id);
            if (!($cached instanceof HistoryRecord)) {
                throw new EmptyValueException('Cached history not instance of HistoryRecord');
            }
            return $cached;
        } catch (EmptyValueException $ex) {
        }
        /** @var array<string, string> $hist */
        $hist = $this->ezdb->row("SELECT * FROM herd_history WHERE id = ?", $id);
        if (empty($hist)) {
            throw new EmptyValueException('History #' . $id . ' not found');
        }
        $history = new HistoryRecord(
            new SigningPublicKey(
                Base64UrlSafe::decode($hist['publickey'])
            ),
            $hist['signature'],
            $hist['contents'],
            $hist['hash'],
            $hist['summaryhash'],
            $hist['prevhash'],
            !empty($hist['accepted'])
        );
        ObjectCache::set($history, 'history', $id);
        return $history;
    }

    /**
     * @param int $id
     * @return Product
     * @throws EmptyValueException
     */
    public function loadProduct(int $id): Product
    {
        try {
            $cached = ObjectCache::get('product', $id);
            if (!($cached instanceof Product)) {
                throw new EmptyValueException('Cached product not instance of Product');
            }
            return $cached;
        } catch (EmptyValueException $ex) {
        }
        /** @var array<string, string|int|bool> $prod */
        $prod = $this->ezdb->row("SELECT * FROM herd_products WHERE id = ?", $id);
        if (empty($prod)) {
            throw new EmptyValueException('Product #' . $id . ' not found');
        }

        $vendor = (int) $prod['vendor'];
        $product = new Product(
            $this->loadVendor($vendor),
            (string) $prod['name']
        );
        ObjectCache::set($product, 'product', $id);
        return $product;
    }

    /**
     * @param int $id
     * @return Release
     * @throws EmptyValueException
     */
    public function loadProductRelease(int $id): Release
    {
        try {
            $cached = ObjectCache::get('release', $id);
            if (!($cached instanceof Release)) {
                throw new EmptyValueException('Cached release not instance of Release');
            }
            return $cached;
        } catch (EmptyValueException $ex) {
        }
        /** @var array<string, string|int|bool> $r */
        $r = $this->ezdb->row("SELECT * FROM herd_product_updates WHERE id = ?", $id);
        if (empty($r)) {
            throw new EmptyValueException('Product Release #' . $id . ' not found');
        }
        $release = new Release(
            $this->loadProduct((int) $r['product']),
            $this->loadHistory((int) $r['history']),
            (string) $r['version'],
            (string) $r['body'],
            (string) Base64UrlSafe::decode((string) $r['signature'])
        );
        ObjectCache::set($release, 'release', $id);
        return $release;
    }

    /**
     * @param int $id
     * @return Vendor
     * @throws EmptyValueException
     */
    public function loadVendor(int $id): Vendor
    {
        try {
            $cached = ObjectCache::get('vendor', $id);
            if (!($cached instanceof Vendor)) {
                throw new EmptyValueException('Cached vendor not instance of Vendor');
            }
            return $cached;
        } catch (EmptyValueException $ex) {
        }

        /** @var string $name */
        $name = (string) $this->ezdb->cell("SELECT name FROM herd_vendors WHERE id = ?", $id);
        if (empty($name)) {
            throw new EmptyValueException('Vendor #' . $id . ' not found');
        }
        $vendor = new Vendor($name);
        /** @var array<int, array<string, string>> $vendorKeys */
        $vendorKeys = $this->ezdb->run("SELECT publickey FROM herd_vendor_keys WHERE trusted");
        foreach ($vendorKeys as $vk) {
            /** @var array<string, string> $vk */
            $vendor->appendPublicKey(
                new SigningPublicKey(
                    Base64UrlSafe::decode((string) $vk['publickey'])
                )
            );
        }
        ObjectCache::set($vendor, 'vendor', $id);
        return $vendor;
    }

    /**
     * @param \PDO $obj
     * @return self
     */
    public static function fromPDO(\PDO $obj): self
    {
        return new self(new EasyDB($obj));
    }
}
