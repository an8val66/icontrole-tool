#!/usr/bin/env php
<?php

define('ENV', 'dev');

$basePath = getcwd();

if (file_exists("$basePath/vendor/autoload.php")) {
    require_once "$basePath/vendor/autoload.php";
} elseif (\Phar::running()) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log('Error: I cannot find the autoloader of the application.' . PHP_EOL);
    exit(2);
}

require __DIR__ . '/src/cli.php';
