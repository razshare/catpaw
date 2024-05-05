<?php

namespace CatPaw\Superstyle;

use CatPaw\Core\Container;
use CatPaw\Superstyle\Services\SuperstyleService;
use function CatPaw\Web\failure;
use CatPaw\Web\Interfaces\RenderContextInterface;
use CatPaw\Web\Interfaces\ResponseModifier;

use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;

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

        $result = $superstyle->file(
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

        $document = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
                <style>{$result->getGlobals()}{$result->css}</style>
            </head>
            <body>{$result->html}</body>
            </html>
            HTML;

        return success($document, $status, $headers)->as(TEXT_HTML);
    }
}
