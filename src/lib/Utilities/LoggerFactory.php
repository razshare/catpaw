<?php

namespace CatPaw\Utilities;

use function Amp\ByteStream\getStdout;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory {
    private function __construct() {
    }

    public static function create(string $loggerName = 'Logger'): LoggerInterface {
        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());
        $logger = new Logger($loggerName);
        $logger->pushHandler($handler);
        return $logger;
    }
}