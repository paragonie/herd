<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    GetOpt,
    Operand,
    Option
};
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    ConfigurableTrait,
    DatabaseTrait
};
use ParagonIE\Herd\Config;
use ParagonIE\Herd\Data\Local;
use ParagonIE\Herd\Exception\{
    EncodingError,
    FilesystemException
};
use ParagonIE\Herd\Herd;
use ParagonIE\Herd\History;

/**
 * Class Transcribe
 * @package ParagonIE\Herd\CommandLine\Command
 */
class Transcribe implements CommandInterface
{
    use ConfigurableTrait;
    use DatabaseTrait;

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
     * @throws EncodingError
     * @throws FilesystemException
     */
    public function run(...$args): int
    {
        $history = new History(
            new Herd(
                new Local($this->getDatabase($this->configPath)),
                Config::fromFile($this->configPath)
            )
        );
        try {
            $history->transcribe();
        } catch (\Throwable $ex) {
            echo $ex->getMessage(), PHP_EOL;
            return 127;
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
        return [];
    }
}
