<?php
namespace ParagonIE\Herd\Tests;

use ParagonIE\EasyDB\{
    EasyDB,
    Factory
};

require \dirname(__DIR__) . '/vendor/autoload.php';

$path = $argv[1] ?? ':memory:';

/** @var $db EasyDB */
$db = Factory::create('sqlite:' . $path);

$db->beginTransaction();
$contents = \file_get_contents(\dirname(__DIR__) . '/sql/sqlite/tables.sql');
if (!\is_string($contents)) {
    die('Could not read contents');
}
$db->getPdo()->exec($contents);
if (!$db->commit()) {
    var_dump($db->getPdo()->errorInfo());
}
