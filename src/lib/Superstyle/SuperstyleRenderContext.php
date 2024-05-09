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
        
        
    /** @var false|(callable(SuperstyleDocument):string) $templateBuilder */
    private mixed $templateBuilder = false;
    private string $title          = 'Document';
        
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
     * Build the template template yourself.
     * @param callable(SuperstyleDocument):string $builder A function that takes the 
     *                                                     superstyle document and returns a string, which should be the structure of your HTML document.\
     * 
     *                                                            It should looks something like this
     *                                                            ```php
     *                                                            superstyle('my-file.hbs')->template(
     *                                                            fn (SuperstyleDocument $document) => <<<HTML
     *                                                            <!DOCTYPE html>
     *                                                            <html lang="en">
     *                                                            <head>
     *                                                            <meta charset="UTF-8">
     *                                                            <meta name="viewport content="width=device-width, initial-scale=1.0">
     *                                                            <title>My title</title>
     *                                                            </head>
     *                                                              <body>
     *                                                               <style>{$document->style}</style>
     *                                                               {$document->markup}
     *                                                               <script>{$document->script}</script>
     *                                                              </body>
     *                                                            </html>
     *                                                            HTML
     *                                                            )
     *                                                            ```
     * @return self
     */
    public function template(callable $builder):self {
        $this->templateBuilder = $builder;
        return $this;
    }
    public function withTitle(string $title):self {
        $this->title = $title;
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

        $documentLocal = $superstyle->file($this->fileName, $this->context)->unwrap($errorSuperstyle);

        $document = new SuperstyleDocument(
            title: $this->title,
            markup: $documentLocal->markup,
            style: $documentLocal->style,
            script: $documentLocal->script,
        );
        

        if ($errorSuperstyle) {
            $logger = Container::create(LoggerInterface::class)->unwrap($errorLogger);
            if ($errorLogger) {
                echo $errorLogger.PHP_EOL;
                echo $errorSuperstyle.PHP_EOL;
            }
            $logger->error((string)$errorSuperstyle);
            return failure();
        }

        $content = match ((bool)$this->templateBuilder) {
            true  => ($this->templateBuilder)($document),
            false => <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>$document->title</title>
                </head>
                <body>
                    <style>{$document->style}</style>
                    {$document->markup}
                    <script>{$document->script}</script>
                </body>
                </html>
                HTML,
        };

        return success($content, $status, $headers)->as(TEXT_HTML);
    }
}
