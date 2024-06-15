<?php

namespace CatPaw\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggerFactory {
    private function __construct() {
    }

    /**
     * Create a logger.
     * @param  string                  $loggerName
     * @return Unsafe<LoggerInterface>
     */
    public static function create(string $loggerName = 'Logger'): Unsafe {
        try {
            $handler = new StreamHandler(STDOUT);
            $handler->setFormatter(new \Monolog\Formatter\SyslogFormatter($loggerName));
            $logger = new Logger($loggerName);
            $logger->pushHandler($handler);
            // @phpstan-ignore-next-line
            return ok($logger);
        } catch(Throwable $e) {
            return error($e);
        }
    }
}
