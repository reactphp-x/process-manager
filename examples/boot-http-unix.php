<?php

$http = new \React\Http\HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) {
    return \React\Http\Message\Response::plaintext(
        "Hello World!\n" . getenv('UUID')
        . json_encode($request->getHeaders(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)
        . json_encode($request->getServerParams(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)
    );
});

$uuid = getenv('UUID');
$path = "/var/run/process-manager-child-{$uuid}.sock";

if (file_exists($path)) {
    unlink($path);
}

$http->listen(new \React\Socket\SocketServer("unix://{$path}"));

echo "【client $uuid listened At://unix:/{$path}】\n";