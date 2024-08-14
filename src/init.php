<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../autoload.php';
}

if (getenv('BOOT_FILE')) {
    require_once getenv('BOOT_FILE');
}

use ReactphpX\Bridge\Client;
use React\EventLoop\Loop;

$uri = getenv('URI');
$uuid = getenv('UUID');
$secret = getenv('SECRET');
$debug = getenv('DEBUG');

Client::$secretKey = $secret;
Client::$debug = $debug ? true : false;


$client = new Client($uri, $uuid);
$client->start();



$start = time();

Loop::addPeriodicTimer(10, function () use ($uuid, $start) {
    $memoryUsage = memory_get_usage();
    $memoryUsageInM = round($memoryUsage / 1024 / 1024, 2);
    $end = time();
    $run_time = $end - $start;
    $date = date('Y-m-d H:i:s');
    var_export([
        'uuid' => $uuid,
        'date' => $date,
        'memory' => "{$memoryUsageInM} MB",
        'run_time' => $run_time,
    ]);
});

Loop::addPeriodicTimer(60, function () {
    gc_collect_cycles();
});



return $client;
