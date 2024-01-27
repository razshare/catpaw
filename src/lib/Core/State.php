<?php
namespace CatPaw\Core;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use stdClass;

class State {
    public function __construct() {
        $state            = new stdClass;
        $state->functions = [];
        $state->data      = new stdClass;
        $className        = static::class;
        $reflection       = new ReflectionClass($className);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }
            $name               = $property->getName();
            $value              = $this->$name;
            $state->data->$name = $value;
            unset($this->$name);
        }
        StateContext::set($this, $state);
    }

    private function __destruct() {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }

        unset($state->functions);
        unset($state->data);

        StateContext::unset($this);
    }

    private function __get($key) {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        return $state->data->$key ?? null;
    }

    private function __set($key, $value) {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        $state->data->$key = $value;
        $this->activate();
    }

    private function __isset($key) {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        return isset($state->data->$key);
    }

    private function __unset($key) {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        unset($state->data->$key);
        $this->activate();
    }

    /**
     * Invoke bound functions.
     * @return void
     */
    private function activate() {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        foreach ($state->functions as $function) {
            $result = $function();
            if ($result instanceof Unsafe) {
                $result->try($error);
                if ($error) {
                    if (!$state->logger) {
                        $state->logger = Container::create(LoggerInterface::class)->try($error);
                    }
                    if ($error) {
                        echo (string)$error;
                        return;
                    }
                    ($state->logger)((string)$error);
                    return;
                }
            }
        }
    }

    /**
     * Run a `$function` when the state changes.
     * @param  callable $function
     * @return void
     */
    public function run(callable $function):void {
        /** @var false|stdClass */
        static $state = false;
        if (!$state) {
            $state = StateContext::get($this);
        }
        $state->functions[] = $function;
        $result             = $function();
        if ($result instanceof Unsafe) {
            $result->try($error);
            if ($error) {
                if (!$this->logger) {
                    $this->logger = Container::create(LoggerInterface::class)->try($error);
                }
                if ($error) {
                    echo (string)$error;
                    return;
                }
                ($this->logger)((string)$error);
            }
        }
    }
}
