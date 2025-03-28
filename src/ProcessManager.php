<?php

namespace ReactphpX\ProcessManager;

use React\ChildProcess\Process;
use ReactphpX\Pool\AbstractConnectionPool;
use ReactphpX\TunnelStream\TunnelStream;
use ReactphpX\Concurrent\Concurrent;

class ProcessManager extends AbstractConnectionPool
{
    public function __construct(string $command, int $minIdleProcesses = 1, int $maxProcesses = 1, int $waitQueue = 100, int $waitTimeout = 10)
    {
        parent::__construct($command, $minIdleProcesses, $maxProcesses, $waitQueue, $waitTimeout);
    }

    public function createConnection()
    {
        $process = new Process($this->uri);
        $process->start();
        $this->currentConnections++;

        $wraper = new class($process) {
            public function __construct(protected Process $process) {}

            public function getProcess(): Process
            {
                return $this->process;
            }

            public function ping()
            {
                return \React\Promise\resolve(true);
            }


            public function close()
            {
                foreach ($this->process->pipes as $pipe) {
                    $pipe->close();
                }
                $this->process->terminate();
            }
        };

        $process->on('exit', function ($exitCode, $termSignal) use ($wraper) {
            if ($this->pool->contains($wraper)) {
                $this->pool->detach($wraper);
            }
            $this->currentConnections--;
        });

        return $wraper;
    }

    public function run(callable $callable, $prioritize = 0)
    {
        $concurrent = new Concurrent(1, 0, true);
        $shadow = new class() {
            public $wraper;
            public $tunnelStream;
        };

        $streamPromise = $concurrent->concurrent(fn() => $this->getConnection($prioritize)->then(function ($wraper) use ($callable, $shadow) {
            $tunnelStream = new TunnelStream($wraper->getProcess()->stderr, $wraper->getProcess()->stdin);
            $shadow->wraper = $wraper;
            $shadow->tunnelStream = $tunnelStream;
            return $tunnelStream->run($callable);
        }));

        // 当streamPromise结束时，释放连接
        $concurrent->concurrent(function () use ($shadow) {
            // 释放连接
            if ($shadow->tunnelStream) {
                $shadow->tunnelStream->close();
                $shadow->tunnelStream = null;
            }
            // 释放连接
            if ($shadow->wraper) {
                $this->releaseConnection($shadow->wraper);
            }

            $shadow = null;
        });

        return $streamPromise;
    }
}
