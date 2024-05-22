<?php
namespace CatPaw\Twig;

use CatPaw\Core\Container;
use function CatPaw\Web\failure;
use CatPaw\Web\Interfaces\RenderContextInterface;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Services\ViewService;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;
use Psr\Log\LoggerInterface;

class TwigRenderContext implements RenderContextInterface {
    /**
     *
     * @param  string       $name
     * @param  array<mixed> $context
     * @return void
     */
    public function __construct(
        private readonly string $name,
        private array $context = [],
    ) {
    }

    /**
     * Set all properties.
     * @param  array<mixed>      $properties
     * @return TwigRenderContext
     */
    public function withProperties(array $properties):self {
        $this->context = $properties;
        return $this;
    }

    /**
     * Set a property.
     * @param  string            $key
     * @param  mixed             $value
     * @return TwigRenderContext
     */
    public function withProperty(string $key, mixed $value):self {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Unset a property.
     * @param  string            $key
     * @return TwigRenderContext
     */
    public function withoutProperty(string $key):self {
        if (!isset($this->context[$key])) {
            return $this;
        }
        unset($this->context[$key]);
        return $this;
    }
    /**
     * Create a response modifier to render the Twig component.
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return ResponseModifier
     */
    public function response(int $status = 200, array $headers = []):ResponseModifier {
        $twig = Container::get(ViewService::class)->unwrap($errorService);
        if ($errorService) {
            $logger = Container::get(LoggerInterface::class)->unwrap($errorLogger);
            if ($errorLogger) {
                echo $errorLogger.PHP_EOL;
                echo $errorService.PHP_EOL;
            }
            $logger->error((string)$errorService);
            return failure();
        }

        $data = $twig->file(
            fileName  : $this->name,
            context: $this->context,
        )->unwrap($errorTwig);

        if ($errorTwig) {
            $logger = Container::get(LoggerInterface::class)->unwrap($errorLogger);
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
