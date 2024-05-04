<?php

namespace CatPaw\Web;

use CatPaw\Core\Container;
use CatPaw\Core\Interfaces\RenderContextInterface;
use CatPaw\Superstyle\Services\SuperstyleService;
use CatPaw\Web\Interfaces\ResponseModifier;
use Psr\Log\LoggerInterface;

class SuperstyleRenderContext implements RenderContextInterface {
    /**
     *
     * @param  string                  $fileName
     * @param  array<mixed>            $properties
     * @return SuperstyleRenderContext
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
     * @param  array<mixed> $context
     * @return void
     */
    private function __construct(
        private readonly string $fileName,
        private array $context = [],
    ) {
    }

    /**
     *
     * @param  array<mixed>            $properties
     * @return SuperstyleRenderContext
     */
    public function setProperties(array $properties):self {
        $this->context = $properties;
        return $this;
    }

    public function setProperty(string $key, mixed $value):self {
        $this->context[$key] = $value;
        return $this;
    }

    public function unsetProperty(string $key):self {
        if (!isset($this->context[$key])) {
            return $this;
        }
        unset($this->context[$key]);
        return $this;
    }
    /**
     *
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return ResponseModifier
     */
    public function render(int $status = 200, array $headers = []):ResponseModifier {
        $superstyle = Container::create(SuperstyleService::class)->unwrap($errorService);
        if ($errorService) {
            $logger = Container::create(LoggerInterface::class)->unwrap($errorLogger);
            if ($errorLogger) {
                echo $errorLogger.PHP_EOL;
                echo $errorService.PHP_EOL;
            }
            $logger->error((string)$errorService);
            return failure();
        }

        $data = $superstyle->file(
            fileName  : $this->fileName,
            context: $this->context,
        )->unwrap($errorTwig);

        if ($errorTwig) {
            $logger = Container::create(LoggerInterface::class)->unwrap($errorLogger);
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
