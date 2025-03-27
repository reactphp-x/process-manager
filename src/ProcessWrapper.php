<?php

namespace ReactphpX\ProcessManager;

use React\ChildProcess\Process;

class ProcessWrapper {
    private Process $process;
    private bool $shared = true;
    private int $usageCount = 0;

    public function __construct(Process $process) {
        $this->process = $process;
    }

    public function getProcess(): Process {
        return $this->process;
    }

    public function isShared(): bool {
        return $this->shared;
    }

    public function setShared(bool $shared): void {
        $this->shared = $shared;
    }

    public function getUsageCount(): int {
        return $this->usageCount;
    }

    public function incrementUsageCount(): void {
        $this->usageCount++;
    }

    public function resetUsageCount(): void {
        $this->usageCount = 0;
    }
}
