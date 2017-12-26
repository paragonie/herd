<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine;
use ParagonIE\Herd\Exception\FilesystemException;

/**
 * Trait ConfigurableTrait
 * @package ParagonIE\Herd\CommandLine\Command
 */
trait ConfigurableTrait
{
    /** @var string $configPath */
    public $configPath = '';

    /**
     * @param string $path
     * @throws FilesystemException
     * @return self
     */
    public function setConfigPath(string $path): self
    {
        if (!\file_exists($path)) {
            throw new FilesystemException('Configuration file does not exist');
        }
        if (!\is_readable($path)) {
            throw new FilesystemException('Configuration file cannot be read by the current user');
        }
        if (!\is_writable($path)) {
            throw new FilesystemException('Configuration file cannot be written to by the current user');
        }
        $this->configPath = \realpath($path);
        return $this;
    }
}
