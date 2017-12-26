<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    GetOpt,
    Operand,
    Option
};
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    DatabaseTrait
};
use ParagonIE\Herd\Exception\EncodingError;
use ParagonIE\Herd\Exception\FilesystemException;

/**
 * Class AddRemote
 * @package ParagonIE\Herd\CommandLine\Command
 */
class AddRemote implements CommandInterface
{
    use DatabaseTrait;

    /** @var string $configPath */
    protected $configPath = '';

    /** @var bool $primary */
    protected $primary = false;

    /**
     * @return array<int, Option>
     */
    public function getOptions(): array
    {
        return [
            new Option('c', 'config', GetOpt::OPTIONAL_ARGUMENT),
            new Option('p', 'primary', GetOpt::NO_ARGUMENT)
        ];
    }

    /**
     * @return array<int, Operand>
     */
    public function getOperands(): array
    {
        return [
            new Operand('url', Operand::REQUIRED),
            new Operand('publickey', Operand::REQUIRED)
        ];
    }

    /**
     * @param array<int, string> $args
     * @return int
     * @throws EncodingError
     * @throws FilesystemException
     */
    public function run(...$args): int
    {
        /**
         * @var string $url
         * @var string $publickey
         */
        list ($url, $publickey) = $args;

        $file = \file_get_contents($this->configPath);
        if (!\is_string($file)) {
            throw new FilesystemException('Could not read configuration file');
        }
        /** @var array<string, array<int, array<string, string>>> $decoded */
        $decoded = \json_decode($file, true);
        if (!\is_array($decoded)) {
            throw new EncodingError('Could not decode JSON body in configuration file');
        }
        $pkey = (string) Base64UrlSafe::decode($publickey);
        if (Binary::safeStrlen($pkey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new EncodingError('Public key is not a base64url-encoded Ed25519 public key, but it must be.');
        }

        $decoded['remotes'][] = [
            'url' => $url,
            'public-key' => $publickey,
            'primary' => $this->primary
        ];

        $encoded = \json_encode($decoded, JSON_PRETTY_PRINT);
        if (!\is_string($encoded)) {
            throw new EncodingError('Could not re-encode JSON body');
        }

        /** @var bool|int $saved */
        $saved = \file_put_contents($this->configPath, $encoded);
        if (!\is_int($saved)) {
            throw new FilesystemException('Could not write to configuration file');
        }
        return 0;
    }

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

    /**
     * Use the options provided by GetOpt to populate class properties
     * for this Command object.
     *
     * @param array<string, string> $args
     * @return self
     * @throws FilesystemException
     */
    public function setOpts(array $args = [])
    {
        if (isset($args['config'])) {
            $this->setConfigPath($args['config']);
        } elseif (isset($args['c'])) {
            $this->setConfigPath($args['c']);
        } else {
            $this->setConfigPath(
                \dirname(\dirname(\dirname(__DIR__))) .
                '/data/config.json'
            );
        }

        if (isset($args['primary'])) {
            $this->primary = true;
        } elseif (isset($args['p'])) {
            $this->primary = true;
        }

        return $this;
    }

    /**
     * Get information about how this command should be used.
     *
     * @return array
     */
    public function usageInfo(): array
    {
        return [];
    }
}
