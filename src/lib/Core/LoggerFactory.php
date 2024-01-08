<?php

namespace CatPaw;

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
            // $handler->setFormatter(new ConsoleFormatter());
            $logger = new Logger($loggerName);
            $logger->pushHandler($handler);
            return ok($logger);
        } catch(Throwable $e) {
            return error($e);
        }
    }
}
