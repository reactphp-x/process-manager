<?php

use ReactphpX\Single\Single;
use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\CompositeStream;
use React\Stream\Util;

class ShadomSocket extends EventEmitter implements \React\Socket\ServerInterface
{
    use Single;

    public function getAddress()
    {
        return null;
    }

    public function pause()
    {

    }

    public function resume()
    {

    }

    public function close()
    {

    }
}

class Connection extends EventEmitter implements ConnectionInterface 
{


    public function __construct(private CompositeStream $stream, private $remoteAddress, private $localAddress)
    {
        Util::forwardEvents($this->stream, $this, array('data', 'end', 'error', 'close', 'pipe', 'drain'));

        $this->stream->on('close', array($this, 'close'));

    }


    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    public function isWritable()
    {
        return $this->stream->isWritable();
    }

    public function pause()
    {
        $this->stream->emit('pause');
    }

    public function resume()
    {
        $this->stream->emit('resume');
    }

    public function pipe(\React\Stream\WritableStreamInterface $dest, array $options = array())
    {
        return $this->stream->pipe($dest, $options);
    }

    public function write($data)
    {
        return $this->stream->write($data);
    }

    public function end($data = null)
    {
        $this->stream->end($data);
    }

    public function close()
    {
        $this->stream->close();
    }

    public function getRemoteAddress()
    {
        return $this->remoteAddress;
    }

    public function getLocalAddress()
    {
        return $this->localAddress;
    }

}

$http = new \React\Http\HttpServer(function (\Psr\Http\Message\ServerRequestInterface $request) {
    return \React\Http\Message\Response::plaintext(
        "Hello World!\n"
    );
});

ShadomSocket::instance()->on('open', function ($stream, $remoteAddress, $localAddress) {
    ShadomSocket::instance()->emit('connection', [
        new Connection($stream, $remoteAddress, $localAddress)
    ]);
});


$http->listen(ShadomSocket::instance());

var_dump(111111111);