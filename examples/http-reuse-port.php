<?php

require __DIR__ . '/../vendor/autoload.php';


use ReactphpX\ProcessManager\ProcessManager;
use React\EventLoop\Loop;

ProcessManager::instance('reuse-port')->setNumber(2);
ProcessManager::instance('reuse-port')->setBootFile(__DIR__.'/boot-http-reuse-port.php');
ProcessManager::instance('reuse-port')->start();


