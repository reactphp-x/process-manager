<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\ProcessManager\ProcessManager;
use ReactphpX\TunnelStream\TunnelStream;
use React\EventLoop\Loop;
use function React\Async\await;
use function React\Async\async;

$processManager = new ProcessManager(sprintf(
    'exec php %s/../src/child_process_init.php',
    __DIR__
));

async(function ($processManager) {
    $fileStream = await($processManager->run(function () {
        return file_get_contents(__DIR__.'/../composer.json');
    }));
    
    $fileStream->on('data', function ($data) {
        echo "File Stream: " . $data. PHP_EOL;
    });
    
    $fileStream->on('error', function ($error) {
        echo "File Stream Error: " . $error->getMessage(). PHP_EOL;
    });
    
    $fileStream->on('end', function () {
        echo "File Stream End\n";
    });
    
    $fileStream->on('close', function () {
        echo "File Stream Close\n";
    });
})($processManager);

async(function ($processManager) {
    $promoseStream = await($processManager->run(function () {
        return \React\Promise\Timer\sleep(2)->then(function () {
            return 'Hello World';
        });
    }));
    
    $promoseStream->on('data', function ($data) {
        echo "Promise Stream: " . $data. PHP_EOL;
    });
    
    $promoseStream->on('error', function ($error) {
        echo "Promise Stream Error: " . $error->getMessage(). PHP_EOL;
    });
    $promoseStream->on('end', function () {
        echo "Promise Stream End\n";
    });
    $promoseStream->on('close', function () {
        echo "Promise Stream Close\n";
    });
})($processManager);


async(function ($processManager) {
    $alwayStream = await($processManager->run(function ($stream) {
        $i = 0;
        $timer = Loop::addPeriodicTimer(1, function () use ($stream, &$i) {
            $stream->write('Hello World'. $i.PHP_EOL);
            $i++;
        });
        $stream->on('close', function () use ($timer) {
            Loop::cancelTimer($timer);
            echo "Always Stream Close\n";
        });
        return $stream;
    }));
    
    $alwayStream->on('data', function ($data) {
        echo "Always Stream: " . $data. PHP_EOL;
    });
    
    $alwayStream->on('error', function ($error) {
        echo "Always Stream Error: " . $error->getMessage(). PHP_EOL;
    });
    
    $alwayStream->on('end', function () {
        echo "Always Stream End\n";
    });
    
    $alwayStream->on('close', function () {
        echo "Always Stream Close\n";
    });
    Loop::addTimer(5, function () use ($alwayStream) {
        $alwayStream->close();
    });
})($processManager);





