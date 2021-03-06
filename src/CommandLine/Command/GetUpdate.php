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
 * Class GetUpdate
 * @package ParagonIE\Herd\CommandLine\Command
 */
class GetUpdate implements CommandInterface
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
            new Operand('vendor', Operand::REQUIRED),
            new Operand('product', Operand::REQUIRED),
            new Operand('version', Operand::REQUIRED)
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
        /**
         * @var string $vendor
         * @var string $product
         * @var string $version
         */
        list($vendor, $product, $version) = $args;
        $db = $this->getDatabase($this->configPath);

        /** @var array<string, string> $data */
        $data = $db->row(
            "
                SELECT
                    v.name AS vendor,
                    p.name AS product,
                    h.contents AS history,
                    u.*
                FROM
                    herd_vendors v
                LEFT JOIN
                    herd_products p ON p.vendor = v.id
                LEFT JOIN
                    herd_product_updates u ON u.product = p.id
                WHERE
                    v.name = ?
                    AND p.name = ?
                    AND u.version = ?
            ",
            $vendor,
            $product,
            $version
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
        return [
            'name' => 'Get update information',
            'usage' => 'shepherd get-update <vendor> <product> <version>',
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
