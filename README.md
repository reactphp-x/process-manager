# reactphp-framework-process-manager

## isntall 

```
composer require reactphp-framework/process-manager -vvv

```

## Usage

```php
require __DIR__ . '/../vendor/autoload.php';

use Reactphp\Framework\ProcessManager\ProcessManager;

ProcessManager::$debug = true;

$stream = ProcessManager::instance('cron')->call(function($stream) {
    return 'hello world cron';
});

$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});

```

other handle in different process

```php
require __DIR__ . '/../vendor/autoload.php';

use Reactphp\Framework\ProcessManager\ProcessManager;

ProcessManager::$debug = true;

$stream = ProcessManager::instance('queue')->call(function($stream) {
    return 'hello world queue';
});

$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});
```


set process boot file
```php
// see example/02.php
ProcessManager::instance('queue')->setBootFile(__DIR__.'/boot.php');
```

set process number
```php
ProcessManager::instance('queue')->setNumber(10);

$stream = ProcessManager::instance('queue')->call(function($stream) {
    return 'hello world queue';
});
$stream->on('data', function($data) {
    echo $data.PHP_EOL;
});

$stream->on('close', function() {
    echo 'closed'.PHP_EOL;
});

var_dump(ProcessManager::instance('queue')->getInfo());
```



# License
MIT

 