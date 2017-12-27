<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    GetOpt,
    Operand,
    Option
};
use ParagonIE\Herd\{
    Config, Data\Local, Herd, History
};
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    ConfigurableTrait,
    DatabaseTrait,
    PromptTrait
};

/**
 * Class Review
 * @package ParagonIE\Herd\CommandLine\Command
 */
class Review implements CommandInterface
{
    use ConfigurableTrait;
    use DatabaseTrait;
    use PromptTrait;

    /**
     * @return array<int, Option>
     */
    public function getOptions(): array
    {
        return [
            new Option('c', 'config', GetOpt::REQUIRED_ARGUMENT)
        ];
    }

    /**
     * @return array<int, Operand>
     */
    public function getOperands(): array
    {
        return [];
    }

    /**
     * @param array<int, string> $args
     * @return int
     * @throws \Exception
     */
    public function run(...$args): int
    {
        $db = $this->getDatabase($this->configPath);
        $herd = new Herd(
            new Local($db),
            Config::fromFile($this->configPath)
        );
        $history = new History($herd);

        $query = " SELECT * FROM herd_history WHERE ";
        if ($db->getDriver() === 'sqlite') {
            $query .= 'accepted = 0';
        } else {
            $query .= 'NOT accepted';
        }

        /** @var array<int, array<string, string>> $data */
        $data = $db->run($query);
        if (empty($data)) {
            echo 'No uncommitted entries in the local history.', PHP_EOL;
            return 0;
        }
        return $this->reviewHistory($history, $data);
    }

    /**
     * @param History $history
     * @param array<int, array<string, string>> $data
     * @return int
     */
    protected function reviewHistory(History $history, array $data): int
    {
        $db = $history->getDatabase();
        /** @var array<string, string> $row */
        list($w, $h) = $this->getScreenSize();
        foreach ($data as $row) {
            echo \str_repeat('-', $w - 1), PHP_EOL;
            echo '-- Hash: ', $row['hash'], PHP_EOL;
            echo '-- Summary Hash: ', $row['summaryhash'], PHP_EOL;
            echo '-- Created: ', $row['created'], PHP_EOL;
            echo '-- Contents:', PHP_EOL;
            echo $row['contents'], PHP_EOL;
            echo \str_repeat('-', $w - 1), PHP_EOL;
            $response = $this->prompt('Accept these changes? (y/N)');

            switch (\strtolower($response)) {
                case 'y':
                case 'yes':
                    try {
                        $history->parseContentsAndInsert(
                            $row['contents'],
                            (int)$row['id'],
                            $row['summaryhash']
                        );
                    } catch (\Throwable $ex) {
                        echo $ex->getMessage(), PHP_EOL;
                        return 1;
                    }
                    echo 'Change accepted.', PHP_EOL;
                    break;
                default:
                    echo 'Change rejected!', PHP_EOL;
            }
        }
        return 0;
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
            'name' => 'Review uncommitted updates',
            'usage' => 'shepherd review',
            'options' => [
                'Configuration file' => [
                    'Examples' => [
                        '-c /path/to/file',
                        '--config=/path/to/file'
                    ]
                ]
            ]
        ];
    }
}
