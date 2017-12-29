<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    GetOpt,
    Operand,
    Option
};
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\Herd\CommandLine\{
    ConfigurableTrait,
    CommandInterface,
    DatabaseTrait
};
use ParagonIE\Herd\Config;
use ParagonIE\Herd\Data\Local;
use ParagonIE\Herd\Exception\{
    EmptyValueException,
    EncodingError,
    FilesystemException
};
use ParagonIE\Herd\Herd;
use ParagonIE\Sapient\Sapient;

/**
 * Class Fact
 * @package ParagonIE\Herd\CommandLine\Command
 */
class Fact implements CommandInterface
{
    use ConfigurableTrait;
    use DatabaseTrait;

    /** @var bool $remoteSearch */
    protected $remoteSearch = false;

    /**
     * @return array<int, Option>
     */
    public function getOptions(): array
    {
        return [
            new Option('c', 'config', GetOpt::REQUIRED_ARGUMENT),
            new Option('r', 'remote', GetOpt::NO_ARGUMENT)
        ];
    }

    /**
     * @return array<int, Operand>
     */
    public function getOperands(): array
    {
        return [
            new Operand('hash', Operand::REQUIRED)
        ];
    }

    /**
     * @param array<int, string> $args
     * @return int
     * @throws \Exception
     * @throws \Error
     */
    public function run(...$args): int
    {
        /** @var string $arg1 */
        $arg1 = \array_shift($args);

        $db = $this->getDatabase($this->configPath);
        /** @var array<string, string> $data */
        $data = $db->row(
            "SELECT * FROM herd_history WHERE summaryhash = ?",
            $arg1
        );
        if (empty($data)) {
            if (!$this->remoteSearch) {
                echo '[]', PHP_EOL;
                exit(2);
            }
            $data = $this->lookup($db, $arg1);
            if (empty($data)) {
                echo '[]', PHP_EOL;
                exit(2);
            }
            $data['notice'] = 'This came from a remote source, and is not stored locally!';
        }

        // We don't need this to display:
        unset($data['id']);

        // Convert to bool
        $data['accepted'] = !empty($data['accepted']);

        /** @var string $encoded */
        $encoded = \json_encode($data, JSON_PRETTY_PRINT);
        if (!\is_string($encoded)) {
            throw new EncodingError('Could not encode fact into a JSON string');
        }
        echo $encoded, PHP_EOL;
        return 0;
    }

    /**
     * @param EasyDB $db
     * @param string $summaryHash
     * @return array
     *
     * @throws EmptyValueException
     * @throws EncodingError
     * @throws FilesystemException
     */
    protected function lookup(EasyDB $db, string $summaryHash): array
    {
        $herd = new Herd(
            new Local($db),
            Config::fromFile($this->configPath)
        );
        $remote = $herd->selectRemote(true);
        $sapient = new Sapient();
        try {
            /** @var array<string, string|array> $decoded */
            $decoded = $sapient->decodeSignedJsonResponse(
                $remote->lookup($summaryHash),
                $remote->getPublicKey()
            );
            if ($decoded['status'] === 'OK') {
                if (!\is_array($decoded['results'])) {
                    return [];
                }
                return (array) \array_shift($decoded['results']);
            }
        } catch (\Throwable $ex) {
        }
        return [];
    }

    /**
     * Use the options provided by GetOpt to populate class properties
     * for this Command object.
     *
     * @param array<string, string> $args
     * @return self
     * @throws \Exception
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
        if (isset($args['remote'])) {
            $this->remoteSearch = !empty($args['remote']);
        } elseif (isset($args['r'])) {
            $this->remoteSearch = !empty($args['r']);
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
        return [
            'name' => 'Learn about a historical event.',
            'usage' => 'shepherd fact <summary-hash>',
            'options' => [
                'Configuration file' => [
                    'Examples' => [
                        '-c /path/to/file',
                        '--config=/path/to/file'
                    ]
                ],
                'Remote lookup?' => [
                    'Info' => 'If there is no local data, look it up from a remote source.',
                    'Examples' => [
                        '-r',
                        '--remote'
                    ]
                ]
            ]
        ];
    }
}
