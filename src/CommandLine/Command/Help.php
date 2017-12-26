<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    Operand,
    Option
};
use ParagonIE\Herd\CommandLine\CommandInterface;

/**
 * Class Help
 * @package ParagonIE\Herd\CommandLine\Command
 */
class Help implements CommandInterface
{
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
     * Return the size of hte current terminal window
     *
     * @return array<int, int>
     * @psalm-suppress
     */
    public function getScreenSize()
    {
        $output = [];
        \preg_match_all(
            "/rows.([0-9]+);.columns.([0-9]+);/",
            \strtolower(\exec('stty -a | grep columns')),
            $output
        );
        /** @var array<int, array<int, int>> $output */
        if (\sizeof($output) === 3) {
            /** @var array<int, int> $width */
            $width = $output[2];
            /** @var array<int, int> $height */
            $height = $output[1];
            return [
                $width[0],
                $height[0]
            ];
        }
        return [80, 25];
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
         * @var int $w
         * @var int $h
         */
        list($w, $h) = $this->getScreenSize();

        // Header
        echo \str_pad('Command', $maxLength + 2, ' ', STR_PAD_RIGHT),
            '| Information',
            PHP_EOL;

        /**
         * @var string $command
         * @var array $usageInfo
         */
        foreach ($commands as $command => $usageInfo) {
            echo \str_repeat('-', $maxLength + 2),
                '+',
                \str_repeat('-', $w - $maxLength - 3),
                PHP_EOL;
            echo \str_pad($command, $maxLength + 2, ' ', STR_PAD_RIGHT),
                '| ';

            // Display format. Temporarily, JSON.
            $encoded = \json_encode($usageInfo, JSON_PRETTY_PRINT);
            if (!\is_string($encoded)) {
                continue;
            }
            if ($encoded === '[]') {
                echo '(No help information available.)', PHP_EOL;
                continue;
            }
            $usage = \explode(PHP_EOL, $encoded);
            echo \implode(PHP_EOL . \str_repeat(' ', $maxLength + 2 ). '| ', $usage);
            echo PHP_EOL;
        }
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

        return 0;
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
