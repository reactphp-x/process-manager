<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\ProcessManager\ProcessManager;
use React\EventLoop\Loop;

ProcessManager::$debug = true;

ProcessManager::instance('queue')->setBootFile(__DIR__.'/boot.php');
ProcessManager::instance('queue')->setNumber(2);
$stream = ProcessManager::instance('queue')->call(function($stream) {
    return hello();
});

$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});

var_dump(ProcessManager::instance('queue')->getInfo());



Loop::addTimer(2, function () {
    // ProcessManager::instance('queue')->stop();
});




