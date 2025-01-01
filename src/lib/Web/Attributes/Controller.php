<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\error;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Web\Interfaces\RouterInterface;
use ReflectionClass;

/**
 * Promote a class to a controller.
 * @package App\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Controller extends Provider {
    private RouterInterface $router;

    public function __construct(private string $path) {
        parent::__construct(singleton:true);
    }

    /**
     * 
     * @return Result<None>
     */
    #[Entry] public function start(RouterInterface $router):Result {
        $this->router = $router;
        return ok();
    }

    public function onClassInstantiation(
        ReflectionClass $reflectionClass,
        mixed &$instance,
        array $dependencies,
    ): Result {
        parent::onClassInstantiation($reflectionClass, $instance, $dependencies)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $reflectionMethods = $reflectionClass->getMethods();

        foreach ($reflectionMethods as $reflectionMethod) {
            $methodName     = $reflectionMethod->getName();
            $symbolicMethod = strtoupper($methodName);
            $symbolicPath   = $this->path;
            $method         = $reflectionMethod->getClosure($instance);

            $this->router->custom($symbolicMethod, $symbolicPath, $method)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        return ok();
    }
}