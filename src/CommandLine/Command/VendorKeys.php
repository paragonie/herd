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
use ParagonIE\Herd\Exception\EncodingError;

/**
 * Class VendorKeys
 * @package ParagonIE\Herd\CommandLine\Command
 */
class VendorKeys implements CommandInterface
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
        return [
            new Operand('vendor', Operand::REQUIRED)
        ];
    }

    /**
     * @param array<int, string> $args
     * @return int
     * @throws \Exception
     */
    public function run(...$args): int
    {
        /**
         * @var string $vendor
         */
        $vendor = \array_shift($args);
        $db = $this->getDatabase($this->configPath);

        /** @var array<string, string> $data */
        $data = $db->run(
            "
                SELECT
                    v.name AS vendor,
                    k.publickey,
                    k.name,
                    k.created,
                    k.modified,
                    h.contents AS history,
                    h.hash,
                    h.summaryhash
                FROM
                    herd_vendors v
                LEFT JOIN
                    herd_vendor_keys k ON k.vendor = v.id
                LEFT JOIN
                    herd_history h ON k.history_create = h.id
                WHERE
                    v.name = ?
                    AND k.trusted
            ",
            $vendor
        );
        if (empty($data)) {
            echo '[]', PHP_EOL;
            exit(2);
        }

        /** @var string $encoded */
        $encoded = \json_encode($data, JSON_PRETTY_PRINT);
        if (!\is_string($encoded)) {
            throw new EncodingError('Could not encode fact into a JSON string');
        }
        echo $encoded, PHP_EOL;
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
