<?php

require __DIR__ . '/../vendor/autoload.php';


use ReactphpX\ProcessManager\ProcessManager;
use React\EventLoop\Loop;
use function React\Async\async;
use function React\Async\delay;

// ProcessManager::$debug = true;

ProcessManager::instance('master-worker')->setNumber(2);
ProcessManager::instance('master-worker')->setBootFile(__DIR__.'/boot-http-unix.php');
ProcessManager::instance('master-worker')->start();


$socket = new React\Socket\SocketServer('0.0.0.0:8085');

$socket->on('connection', async(function (\React\Socket\ConnectionInterface $connection) {
    $remoteAddress = $connection->getRemoteAddress();
    $localAddress = $connection->getLocalAddress();
    $number = count(ProcessManager::instance('master-worker')->getInfo()['configs']);
   

    if ($number === 0) {
        $connection->end("HTTP/1.1 503 Service Unavailable\r\n\r\n");
        return;
    }

    $buffer = '';
    $connection->on('data', $fn = function ($data) use (&$buffer) {
        $buffer .= $data;
    });

    $connector = new \React\Socket\Connector();

    // todo 将远程地址传递给子进程(解析header)


    $uuid = mt_rand(1, $number);
    $path = "/var/run/process-manager-child-{$uuid}.sock";

    echo "【connect to://unix:/{$path}】\n";

    $connector->connect("unix://$path")->then(function (\React\Socket\ConnectionInterface $stream) use ($connection, $remoteAddress, $localAddress, &$buffer, $fn) {

        if ($buffer !== '') {
            $stream->write($buffer);
            $buffer = '';
        }

        $connection->removeListener('data', $fn);

        $stream->pipe($connection);
        $connection->pipe($stream);

        $stream->on('close', function () use ($connection) {
            $connection->end();
        });

        $connection->on('close', function () use ($stream) {
            $stream->close();
        });

    }, function ($e) use ($connection) {
        $connection->end("HTTP/1.1 503 Service Unavailable\r\n\r\n");
    });


}));