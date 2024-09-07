<?php

require __DIR__ . '/../vendor/autoload.php';


use ReactphpX\ProcessManager\ProcessManager;
use React\EventLoop\Loop;
use function React\Async\async;

// ProcessManager::$debug = true;

ProcessManager::instance('master-worker')->setNumber(2);
ProcessManager::instance('master-worker')->setBootFile(__DIR__.'/boot-http.php');
ProcessManager::instance('master-worker')->start();


$socket = new React\Socket\SocketServer('0.0.0.0:8085');

$socket->on('connection', async(function (\React\Socket\ConnectionInterface $connection) {
    $remoteAddress = $connection->getRemoteAddress();
    $localAddress = $connection->getLocalAddress();

    $buffer = '';
    $connection->on('data', $fn = function ($data) use (&$buffer) {
        $buffer .= $data;
    });

    $stream = ProcessManager::instance('master-worker')->call(function ($stream) use ($remoteAddress, $localAddress) {
        ShadomSocket::instance()->emit('open', [$stream, $remoteAddress, $localAddress]);
        return $stream;
    });

    if ($buffer !== '') {
        $stream->write($buffer);
        $buffer = '';
    }
    
    $connection->removeListener('data', $fn);


    $stream->on('data', function ($data) use ($connection) {
        $connection->write($data);
    });

    $stream->on('end', function ($data = null) use ($connection) {
        if ($data !== null) {
            $connection->end($data);
        } else {
            $connection->end();
        }
    });

    $stream->on('close', function () use ($connection) {
        $connection->close();
    });

    $stream->on('pause', function () use ($connection) {
        $connection->pause();
    });

    $stream->on('resume', function () use ($connection) {
        $connection->resume();
    });

    $connection->on('data', function ($data) use ($stream) {
        $stream->write($data);
    });

    $connection->on('close', function () use ($stream) {
        $stream->close();
    });

    $connection->on('error', function ($error) use ($stream) {
        $stream->emit('error', [$error]);
    });

    $connection->on('end', function () use ($stream) {
        $stream->end();
    });

    $connection->on('drain', function () use ($stream) {
        $stream->resume();
    });

    $stream->on('error', function ($error) use ($connection) {
        var_dump([
            'error' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ], 99999999);
        $connection->close();
    });


}));