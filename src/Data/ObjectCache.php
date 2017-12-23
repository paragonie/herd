<?php
declare(strict_types=1);
namespace ParagonIE\Herd\Data;

use ParagonIE\Herd\Exception\EmptyValueException;

/**
 * Class ObjectCache
 *
 * Cache references to objects in memory to save on DB lookups.
 *
 * @package ParagonIE\Herd\Data
 */
abstract class ObjectCache
{
    /** @var array<string, Cacheable> */
    public static $cache = [];

    /** @var string $cacheKey */
    public static $cacheKey = '';

    /**
     * Get the cache key for this type.
     *
     * @param string $type
     * @return string
     */
    public static function getCacheKey(string $type): string
    {
        if (empty(static::$cacheKey)) {
            static::$cacheKey = \random_bytes(\SODIUM_CRYPTO_SHORTHASH_KEYBYTES);
        }
        return \sodium_bin2hex(
            \sodium_crypto_shorthash(
                $type,
                static::$cacheKey
            )
        );
    }

    /**
     * @param string $type
     * @param int $id
     * @return Cacheable
     * @throws EmptyValueException
     */
    public static function get(string $type, int $id = 0): Cacheable
    {
        $key = static::getCacheKey($type);
        if (empty(static::$cache[$key])) {
            throw new EmptyValueException('No data cached of this type.');
        }
        if (empty(static::$cache[$key][$id])) {
            throw new EmptyValueException('Cache miss.');
        }
        /** @var Cacheable $c */
        $c = static::$cache[$key][$id];
        return $c;
    }

    /**
     * @param string $type
     * @param int $id
     * @return bool
     */
    public static function remove(string $type, int $id): bool
    {
        $key = static::getCacheKey($type);
        if (empty(static::$cache[$key])) {
            return false;
        }
        if (empty(static::$cache[$key][$id])) {
            return false;
        }
        unset(static::$cache[$key][$id]);
        return true;
    }

    /**
     * @param Cacheable $obj
     * @param string $type
     * @param int $id
     * @return Cacheable
     */
    public static function set(Cacheable $obj, string $type, int $id): Cacheable
    {
        $key = static::getCacheKey($type);
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = [];
        }
        static::$cache[$key][$id] = $obj;
        return $obj;
    }
}
