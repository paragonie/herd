<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine;

use GetOpt\{
    Operand,
    Option
};

/**
 * Interface CommandInterface
 * @package ParagonIE\Herd\CommandLine
 */
interface CommandInterface
{
    /**
     * @return array<int, Option>
     */
    public function getOptions(): array;

    /**
     * @return array<int, Operand>
     */
    public function getOperands(): array;

    /**
     * @param array<int, string> $args
     * @return int
     */
    public function run(...$args): int;

    /**
     * Use the options provided by GetOpt to populate class properties
     * for this Command object.
     *
     * @param array<string, string> $args
     * @return self
     */
    public function setOpts(array $args = []);

    /**
     * Get information about how this command should be used.
     *
     * @return array
     */
    public function usageInfo(): array;
}
