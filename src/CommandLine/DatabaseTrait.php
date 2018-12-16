<?php
namespace ParagonIE\Herd\CommandLine;

use ParagonIE\EasyDB\{
    EasyDB,
    Factory
};
use ParagonIE\Herd\Exception\{
    EncodingError,
    FilesystemException
};

/**
 * Trait DatabaseTrait
 * @package ParagonIE\Herd\CommandLine
 */
trait DatabaseTrait
{
    /**
     * Get an EasyDB instance, given a configuration file.
     *
     * @param string $configPath
     * @return EasyDB
     * @throws EncodingError
     * @throws FilesystemException
     * @psalm-suppress MixedInferredReturnType I don't even know why
     */
    public function getDatabase(string $configPath = ''): EasyDB
    {
        if (!$configPath) {
            $configPath = \dirname(\dirname(__DIR__)) . '/data/config.json';
        }
        if (!\is_readable($configPath)) {
            throw new FilesystemException('Cannot read from configuration file');
        }
        /** @var string $config */
        $config = \file_get_contents($configPath);
        if (!\is_string($config)) {
            throw new FilesystemException('Error reading from configuration file');
        }
        /** @var array<string, array<string, mixed>> $data */
        $data = \json_decode($config, true);
        if (!\is_array($data)) {
            throw new EncodingError('Could not decode JSON file');
        }
        if (empty($data['database']['dsn'])) {
            return Factory::create('sqlite::memory:');
        }
        if ($data['database']['dsn'] === 'sqlite') {
            return Factory::create((string) $data['database']['dsn']);
        }
        return Factory::create(
            (string) $data['database']['dsn'],
            (string) ($data['database']['username'] ?? ''),
            (string) ($data['database']['password'] ?? ''),
            (array) ($data['database']['options'] ?? [])
        );
    }
}
