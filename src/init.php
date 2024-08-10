<?php

if (getenv('BOOT_FILE')) {
    require getenv('BOOT_FILE');
} else {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require __DIR__ . '/../vendor/autoload.php';
    } else {
        require __DIR__ . '/../../../autoload.php';
    } 
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





Loop::addPeriodicTimer(10, function () use ($uuid) {
    $memoryUsage = memory_get_usage();
    $memoryUsageInM = round($memoryUsage / 1024 / 1024, 2);
    $date = date('Y-m-d H:i:s');
    echo "$date $uuid Memory usage: {$memoryUsageInM} MB\n";
});



return $client;
