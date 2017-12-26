<?php
declare(strict_types=1);
namespace ParagonIE\Herd\CommandLine\Command;

use GetOpt\Option;
use ParagonIE\Herd\CommandLine\{
    CommandInterface,
    DatabaseTrait
};
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
     * @throws EncodingError
     * @throws FilesystemException
     */
    public function run(...$args): int
    {
        $history = new History(
            new Herd(
                new Local($this->getDatabase())
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
