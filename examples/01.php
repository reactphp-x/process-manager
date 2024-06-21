<?php

require __DIR__ . '/../vendor/autoload.php';

use Reactphp\Framework\ProcessManager\ProcessManager;

ProcessManager::$debug = true;

$stream = ProcessManager::instance('cron')->call(function($stream) {
    return 'hello world cron';
});

$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});

ProcessManager::instance('queue')->setNumber(10);
$stream = ProcessManager::instance('queue')->call(function($stream) {
    return 'hello world queue';
});

$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});

var_dump(ProcessManager::instance('queue')->getInfo());




