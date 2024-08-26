<?php

use function React\Async\async;


$http = new \React\Http\HttpServer(async(function (\Psr\Http\Message\ServerRequestInterface $request) {
    return \React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
}));


$socket = new React\Socket\SocketServer('0.0.0.0:8085', [
    'tcp' => [
        'so_reuseport' => true
    ]
]);

$http->listen($socket);
