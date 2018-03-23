<?php

use Phalcon\Di\FactoryDefault\Cli as FactoryDefault;
use ICTool\Cli\Console;


$di = new FactoryDefault();

$di->setShared('config', function () {
    return require __DIR__ . '/config.php';
});

$di->setShared('console', function () {
    $console = new Console($this);

    return $console;
});