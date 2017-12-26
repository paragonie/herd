<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\{
    Operand,
    Option
};
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    DatabaseTrait
};

/**
 * Class ListVendors
 * @package ParagonIE\Herd\CommandLine\Command
 */
class ListVendors implements CommandInterface
{
    use DatabaseTrait;

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
     * @param array<int, string> $args
     * @return int
     */
    public function run(...$args): int
    {

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
        return [];
    }
}