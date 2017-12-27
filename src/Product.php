<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\Data\Cacheable;

/**
 * Class Product
 * @package ParagonIE\Herd
 */
class Product implements Cacheable
{
    /** @var string */
    protected $name;

    /** @var Vendor */
    protected $vendor;

    /**
     * Product constructor.
     *
     * @param Vendor $vendor
     * @param string $name
     */
    public function __construct(Vendor $vendor, string $name)
    {
        $this->vendor = $vendor;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @param EasyDB $db
     * @param int $vendor
     * @param string $name
     * @return int
     * @throws \Exception
     */
    public static function upsert(EasyDB $db, int $vendor, string $name): int
    {
        /** @var int $exists */
        $exists = $db->cell(
            'SELECT id FROM herd_products WHERE vendor = ? AND name = ?',
            $vendor,
            $name
        );
        if ($exists) {
            return (int) $exists;
        }
        return (int) $db->insertGet(
            'herd_products',
            [
                'vendor' => $vendor,
                'name' => $name,
                'created' => (new \DateTime())
                    ->format(\DateTime::ATOM),
                'modified' => (new \DateTime())
                    ->format(\DateTime::ATOM)
            ],
            'id'
        );
    }
}
