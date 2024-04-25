<?php

namespace CatPaw\Web;

use CatPaw\Core\Container;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Services\TwigService;
use Psr\Log\LoggerInterface;

class TwigRenderContext {
    /**
     *
     * @param  string            $fileName
     * @param  array<mixed>      $properties
     * @return TwigRenderContext
     */
    public static function create(
        string $fileName,
        array $properties = [],
    ):self {
        return new self($fileName, $properties);
    }

    /**
     *
     * @param  string       $fileName
     * @param  array<mixed> $properties
     * @return void
     */
    private function __construct(
        private readonly string $fileName,
        private array $properties = [],
    ) {
    }

    /**
     *
     * @param  array<mixed>      $properties
     * @return TwigRenderContext
     */
    public function setProperties(array $properties):self {
        $this->properties = $properties;
        return $this;
    }

    public function setProperty(string $key, mixed $value):self {
        $this->properties[$key] = $value;
        return $this;
    }

    public function unsetProperty(string $key):self {
        if (!isset($this->properties[$key])) {
            return $this;
        }
        unset($this->properties[$key]);
        return $this;
    }
    /**
     *
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return ResponseModifier
     */
    public function render(int $status = 200, array $headers = []):ResponseModifier {
        $twig = Container::create(TwigService::class)->try($errorService);
        if ($errorService) {
            $logger = Container::create(LoggerInterface::class)->try($errorLogger);
            if ($errorLogger) {
                echo $errorLogger.PHP_EOL;
                echo $errorService.PHP_EOL;
            }
            $logger->error((string)$errorService);
            return failure();
        }

        $data = $twig->render(
            fileName  : $this->fileName,
            properties: $this->properties,
        )->try($errorTwig);

        if ($errorTwig) {
            $logger = Container::create(LoggerInterface::class)->try($errorLogger);
            if ($errorLogger) {
                echo $errorLogger.PHP_EOL;
                echo $errorTwig.PHP_EOL;
            }
            $logger->error((string)$errorTwig);
            return failure();
        }
        return success($data, $status, $headers)->as(TEXT_HTML);
    }
}
