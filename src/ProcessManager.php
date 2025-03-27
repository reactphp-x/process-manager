<?php

namespace ReactphpX\ProcessManager;

use React\ChildProcess\Process;

class ProcessManager
{
    private array $processes = [];
    private array $idleProcesses = [];
    private array $sharedProcesses = [];
    private int $minIdleProcesses;
    private int $maxProcesses;
    private string $command;

    public function __construct(string $command, int $minIdleProcesses = 1, int $maxProcesses = 1)
    {
        $this->command = $command;
        $this->minIdleProcesses = $minIdleProcesses;
        $this->maxProcesses = $maxProcesses;

        $this->initializeProcessPool();
    }

    private function initializeProcessPool(): void
    {
        for ($i = 0; $i < $this->minIdleProcesses; $i++) {
            $this->createProcess();
        }
    }

    private function createProcess(): ProcessWrapper
    {
        $process = new Process($this->command);
        $process->on('exit', function ($exitCode, $termSignal) use ($process) {
            $key = array_search($process, array_map(fn($wrapper) => $wrapper->getProcess(), $this->processes));
            if ($key !== false) {
                unset($this->processes[$key]);
                $this->processes = array_values($this->processes);
            }
        });
        $process->start();
        $wrapper = new ProcessWrapper($process);
        $this->processes[] = $wrapper;
        $this->idleProcesses[] = $wrapper;
        return $wrapper;
    }

    public function getProcess(bool $exclusive = false): ?Process
    {
        if (empty($this->idleProcesses)) {
            if (count($this->processes) < $this->maxProcesses) {
                $wrapper = $this->createProcess();
                if ($exclusive) {
                    $wrapper->setShared(false);
                }
                return $wrapper->getProcess();
            }
            return null;
        }

        if ($exclusive) {
            $wrapper = array_pop($this->idleProcesses);
            $wrapper->setShared(false);
            return $wrapper->getProcess();
        } else {
            $wrapper = null;
            if (!empty($this->sharedProcesses)) {
                // 找到使用量最少的进程
                $minUsage = PHP_INT_MAX;
                foreach ($this->sharedProcesses as $sharedWrapper) {
                    $usage = $sharedWrapper->getUsageCount();
                    if ($usage < $minUsage) {
                        $minUsage = $usage;
                        $wrapper = $sharedWrapper;
                    }
                }
            } else {
                $wrapper = array_pop($this->idleProcesses);
                $this->sharedProcesses[] = $wrapper;
            }
            // 增加使用计数
            $wrapper->incrementUsageCount();
            return $wrapper->getProcess();
        }

        // 如果空闲进程数量低于最小值，且总进程数未达到最大值，则创建新进程
        if (
            count($this->idleProcesses) < $this->minIdleProcesses
            && count($this->processes) < $this->maxProcesses
        ) {
            $wrapper = $this->createProcess();
            if ($exclusive) {
                $wrapper->setShared(false);
            } else {
                $this->sharedProcesses[] = $wrapper;
            }
            $wrapper->incrementUsageCount();
            return $wrapper->getProcess();
        }

        return null;
    }

    public function releaseProcess(Process $process): void
    {
        $wrapper = null;
        foreach ($this->processes as $processWrapper) {
            if ($processWrapper->getProcess() === $process) {
                $wrapper = $processWrapper;
                break;
            }
        }

        if ($wrapper === null) {
            return;
        }

        if ($wrapper->isShared()) {
            $key = array_search($wrapper, $this->sharedProcesses);
            if ($key !== false) {
                unset($this->sharedProcesses[$key]);
                $this->sharedProcesses = array_values($this->sharedProcesses);
            }

            if (!in_array($wrapper, $this->sharedProcesses)) {
                $this->idleProcesses[] = $wrapper;
                // 重置使用计数
                $wrapper->resetUsageCount();
            }
        } else {
            $this->idleProcesses[] = $wrapper;
            $wrapper->setShared(true);
            $wrapper->resetUsageCount();
        }
    }

    public function close(): void
    {
        foreach ($this->processes as $wrapper) {
            $process = $wrapper->getProcess();
            foreach ($process->pipes as $pipe) {
                $pipe->close();
            }
            $process->terminate();
        }
    }


}
