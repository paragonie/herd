<?php
declare(strict_types=1);
namespace ParagonIE\Herd;

use ParagonIE\Herd\Exception\{
    EncodingError,
    FilesystemException
};

/**
 * Class Config
 * @package ParagonIE\Herd
 */
class Config
{
    /** @var bool $coreKeyManagement */
    protected $coreKeyManagement = false;

    /** @var bool $coreAutoKeyManagement */
    protected $coreAutoKeyManagement = false;

    /** @var string $coreVendorName */
    protected $coreVendorName = 'paragonie';

    /** @var string $file */
    protected $file = '';

    /** @var bool $minimalHistory */
    protected $minimalHistory = false;

    /** @var int $quorum */
    protected $quorum = 0;

    /** @var array<int, array<string, mixed>> */
    protected $remotes = [];

    /**
     * Can the core vendor replace keys for other vendors?
     *
     * @return bool
     */
    public function allowCoreToManageKeys(): bool
    {
        return $this->coreKeyManagement;
    }

    /**
     * If the core vendor can replace keys for other vendors, is this done
     * automatically (i.e. without user interaction)?
     *
     * If not, the changes are staged for manual inspection.
     *
     * @return bool
     */
    public function allowNonInteractiveKeyManagement(): bool
    {
        return $this->coreAutoKeyManagement;
    }

    /**
     * @return string
     */
    public function getCoreVendorName(): string
    {
        return $this->coreVendorName;
    }

    /**
     * @return bool
     */
    public function getMinimalHistory(): bool
    {
        return $this->minimalHistory;
    }

    /**
     * @return int
     */
    public function getQuorum(): int
    {
        return $this->quorum;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRemotes(): array
    {
        return $this->remotes;
    }

    /**
     * Load configuration from a file.
     *
     * @param string $path
     * @return self
     * @throws FilesystemException
     * @throws EncodingError
     */
    public static function fromFile(string $path = ''): self
    {
        if (!$path) {
            $path = \dirname(__DIR__) . '/data/config.json';
        }
        if (!\is_readable($path)) {
            throw new FilesystemException('Cannot read configuration file.');
        }
        /** @var string $contents */
        $contents = \file_get_contents($path);
        if (!\is_string($contents)) {
            throw new FilesystemException('Error reading configuration file.');
        }
        /** @var array $decode */
        $decode = \json_decode($contents, true);
        if (!\is_array($decode)) {
            throw new EncodingError('Could not decode JSON string in ' . \realpath($path));
        }

        $config = new static();
        $config->coreVendorName = (string) ($decode['core-vendor'] ?? 'paragonie');
        if (!empty($decode['remotes'])) {
            $config->remotes = (array) ($decode['remotes']);
        }

        /** @var array<string, int|bool> $policies */
        $policies = $decode['policies'] ?? [];
        if (!empty($policies['core-vendor-manage-keys-allow'])) {
            $config->coreKeyManagement = true;
            $config->coreAutoKeyManagement = !empty($policies['core-vendor-manage-keys-auto']);
        }

        if (isset($policies['quorum'])) {
            if (\is_int($policies['quorum'])) {
                $config->quorum = (int) $policies['quorum'];
            }
        }

        $config->minimalHistory = !empty($policies['minimal-history']);

        return $config;
    }
}
