# ReactPHP Process Manager

一个基于ReactPHP的进程管理器，用于管理和控制子进程的生命周期，支持进程池和并发控制。

## 特性

- 进程池管理：支持设置最小空闲进程数和最大进程数
- 并发控制：支持等待队列和超时设置
- 流式通信：基于ReactPHP的流式接口，支持双向通信
- 异步操作：支持Promise和异步回调

## 安装

通过Composer安装：

```bash
composer require reactphp-x/process-manager
```

## 基本用法

### 初始化进程管理器

```php
use ReactphpX\ProcessManager\ProcessManager;

$processManager = new ProcessManager(
    'exec php path/to/child_process_init.php',  // 子进程初始化脚本
    1,   // 最小空闲进程数
    1,   // 最大进程数
    100, // 等待队列大小
    10   // 等待超时时间（秒）
);
```

### 运行任务

支持多种任务类型：

1. 文件操作：
```php
$fileStream = await($processManager->run(function () {
    return file_get_contents('path/to/file');
}));

$fileStream->on('data', function ($data) {
    echo "File content: " . $data . PHP_EOL;
});
```

2. Promise任务：
```php
$promiseStream = await($processManager->run(function () {
    return \React\Promise\Timer\sleep(2)->then(function () {
        return 'Hello World';
    });
}));

$promiseStream->on('data', function ($data) {
    echo "Result: " . $data . PHP_EOL;
});
```

3. 持续运行的任务：
```php
$alwaysStream = await($processManager->run(function ($stream) {
    $timer = Loop::addPeriodicTimer(1, function () use ($stream) {
        $stream->write('Periodic task output\n');
    });
    
    $stream->on('close', function () use ($timer) {
        Loop::cancelTimer($timer);
    });
    
    return $stream;
}));
```

## 事件处理

所有流都支持以下事件：

- `data`：接收数据
- `error`：错误处理
- `end`：流结束
- `close`：流关闭

## 依赖

- react/child-process: ^0.6.5
- reactphp-x/pool: ^2.0
- reactphp-x/tunnel-stream: ^1.0
- reactphp-x/concurrent: ^1.0

## 许可证

MIT License