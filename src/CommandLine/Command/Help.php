<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    Operand,
    Option
};
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    PromptTrait
};

/**
 * Class Help
 * @package ParagonIE\Herd\CommandLine\Command
 */
class Help implements CommandInterface
{
    use PromptTrait;

    /** @var string */
    protected $subCommand = '';

    /**
     * @return array<int, Option>
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * @return array<int, Operand>
     */
    public function getOperands(): array
    {
        return [];
    }

    /**
     * @return array<string, array>
     */
    public function getCommands(): array
    {
        /** @var array<string, string> $aliases */
        $aliases = $GLOBALS['commandAliases'];
        $map = [];
        foreach ($aliases as $command => $className) {
            if (empty($className) || empty($command)) {
                continue;
            }
            if ($className === __CLASS__) {
                $usageInfo = $this->usageInfo();
            } else {
                /** @var CommandInterface $class */
                $class = new $className;
                if (!($class instanceof CommandInterface)) {
                    continue;
                }
                $usageInfo = $class->usageInfo();
            }
            $map[$command] = $usageInfo;
        }
        return $map;
    }

    /**
     * @return int
     */
    public function listCommands(): int
    {
        $commands = $this->getCommands();
        $maxLength = 7;
        /** @var string $key */
        foreach (\array_keys($commands) as $key) {
            $maxLength = \max($maxLength, \strlen($key));
        }
        /**
         * @var array<int, int> $size
         * @var int $w
         */
        $size = $this->getScreenSize();
        $w = (int) \array_shift($size);

        // Header
        echo \str_pad('Command', $maxLength + 2, ' ', STR_PAD_RIGHT),
        '| Information',
        PHP_EOL;

        /**
         * @var string $command
         * @var array<string, string> $usageInfo
         */
        foreach ($commands as $command => $usageInfo) {
            echo \str_repeat('-', $maxLength + 2),
            '+',
            \str_repeat('-', $w - $maxLength - 3),
            PHP_EOL;
            echo \str_pad($command, $maxLength + 2, ' ', STR_PAD_RIGHT),
            '| ';

            // Display format. Temporarily, JSON.
            /** @var string $encoded */
            $encoded = $usageInfo['name'] ?? $command;
            $encoded .= PHP_EOL . PHP_EOL;
            $encoded .= (string) ($usageInfo['usage'] ?? ('shepherd ' . $command));

            $usage = \explode(PHP_EOL, $encoded);
            echo \implode(PHP_EOL . \str_repeat(' ', $maxLength + 2 ). '| ', $usage);
            echo PHP_EOL;
        }
        return 0;
    }

    /**
     * @param string $arg
     * @return int
     */
    public function getCommandInfo(string $arg): int
    {
        $commands = $this->getCommands();
        if (!\array_key_exists($arg, $commands)) {
            echo 'Command not found', PHP_EOL;
            return 2;
        }

        $maxLength = 7;
        /** @var string $key */
        foreach (\array_keys($commands) as $key) {
            $maxLength = \max($maxLength, \strlen($key));
        }
        /**
         * @var array<int, int> $size
         * @var int $w
         */
        $size = $this->getScreenSize();
        $w = (int) \array_shift($size);

        // Header
        echo \str_pad('Command', $maxLength + 2, ' ', STR_PAD_RIGHT),
        '| Information',
        PHP_EOL;

        /**
         * @var string $command
         * @var array $usageInfo
         */
        $usageInfo = $commands[$arg];
        echo \str_repeat('-', $maxLength + 2),
        '+',
        \str_repeat('-', $w - $maxLength - 3),
        PHP_EOL;
        echo \str_pad($arg, $maxLength + 2, ' ', STR_PAD_RIGHT),
        '| ';

        // Display format. Temporarily, JSON.
        $encoded = \json_encode($usageInfo, JSON_PRETTY_PRINT);
        if (!\is_string($encoded)) {
            return 1;
        }
        if ($encoded === '[]') {
            echo '(No help information available.)', PHP_EOL;
            return 1;
        }
        $usage = \explode(PHP_EOL, $encoded);
        echo \implode(PHP_EOL . \str_repeat(' ', $maxLength + 2 ). '| ', $usage);
        echo PHP_EOL;
        return 0;
    }

    /**
     * @param array<int, string> $args
     * @return int
     */
    public function run(...$args): int
    {
        if (empty($args)) {
            return $this->listCommands();
        }
        /** @var string $key */
        $key = \array_shift($args);
        return $this->getCommandInfo($key);
    }

    /**
     * Use the options provided by GetOpt to populate class properties
     * for this Command object.
     *
     * @param array $args
     * @return self
     */
    public function setOpts(array $args = [])
    {
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
            'name' => 'Usage Information',
            'details' => 'Learn how to use each command available to this CLI API.'
        ];
    }
}
