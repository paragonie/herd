<?php
declare(strict_types=1);

require_once \dirname(__DIR__) . '/vendor/autoload.php';

use GetOpt\GetOpt;
use ParagonIE\Herd\CommandLine\Command\{
    AddRemote,
    Fact,
    Help,
    GetUpdate,
    ListProducts,
    ListUpdates,
    ListVendors,
    Transcribe,
    VendorKeys
};
use ParagonIE\Herd\CommandLine\CommandInterface;

/**
 * CLI secondary arguments.
 *
 * shepherd <command> (... arguments)
 *
 * @var array<string, string> $commandAliases
 */
$commandAliases = [
    '' => Help::class,
    'add-remote' => AddRemote::class,
    'fact' => Fact::class,
    'get-update' => GetUpdate::class,
    'help'  => Help::class,
    'list-products' => ListProducts::class,
    'list-updates' => ListUpdates::class,
    'list-vendors' => ListVendors::class,
    'transcribe' => Transcribe::class,
    'vendor-keys' => VendorKeys::class
];

/** Do not touch the code below this line unless you absolutely must. **/

// Which command is being executed?
if ($argv[0] === 'php') {
    $alias = $argc > 2 ? $argv[2] : '';
    $args = \array_slice($argv, 3);
} else {
    $alias = $argc > 1 ? $argv[1] : '';
    $args = \array_slice($argv, 2);
}

if (!array_key_exists($alias, $commandAliases)) {
    echo 'Command not found!', PHP_EOL;
    exit(255);
}
$command = $commandAliases[$alias];
if (!\class_exists($command)) {
    echo 'Command not found! (Class does not exist.)', PHP_EOL;
    exit(255);
}
$class = new $command;
if (!($class instanceof CommandInterface)) {
    echo 'Command not an instance of CommandInterface!', PHP_EOL;
    exit(255);
}

// Use GetOpt to process the arguments properly:
$getOpt = new GetOpt($class->getOptions());
$getOpt->process($args);
$opts = $getOpt->getOptions();

// Execute the CLI command.
$exitCode = $class
    ->setOpts($opts)
    ->run(...$args);
exit($exitCode);
