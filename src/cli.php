<?php

use Phalcon\Exception as PhalconException;

if (php_sapi_name() !== 'cli') {
    error_log('Error: The CLI interface is not being  called from the command line.');
    exit(1);
}

if (!defined('ENV')) {
    error_log('Error: ENV is not defined.');
    exit(1);
}

ini_set('user_agent', 'ICTool - IControle command line tool');

require __DIR__ . '/app/config/services.php';

$loader = new \Phalcon\Loader();
$loader->registerDirs(
    [
        __DIR__ . '/app/tasks',
        __DIR__ . '/app/model'
    ]
);

$loader->register();


try {
    $console = $di->getConsole()
        ->handle([
            'defaultCmd' => 'main',
            'params'     => $argv
        ]);
} catch (PhalconException $e) {
    error_log($e->getMessage());
    exit(255);
} catch (\Exception $e) {
    error_log($e->getMessage());
    exit($e->getCode());
}