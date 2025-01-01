<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\ContainerContext;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMountInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\RequestContext;
use ReflectionParameter;

/**
 * Get a parameter from the route path.
 *
 *
 * The name of the parameter must match with the name of the path variable.
 *
 * Given the path `/api/user/{username}/settings`, you would be able to get the `username` path variable using `#[Param] string $username`.
 *
 *
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_PARAMETER)]
class Param implements AttributeInterface, OnParameterMountInterface {
    use CoreAttributeDefinition;

    public function __construct(private string $regex = '') {
        $this->withRegex($regex);
    }

    public function regex():string {
        return $this->regex;
    }

    public function withRegex(string $value):void {
        $this->regex = $value;
    }

    /** @var array<string,mixed>  */
    private static array $cache = [];

    public function onParameterMount(ReflectionParameter $reflectionParameter, mixed &$value, ContainerContext $options):Result {
        /** @var false|RequestContext $context */
        $context = $options->data;
        $name    = $reflectionParameter->getName();

        if (!isset(self::$cache[$context->key])) {
            $type = ReflectionTypeManager::unwrap($reflectionParameter);
            if (!$type) {
                return error("Every path parameter must specify a type but none has been detected in \"$context->key\" for parameter \"$name\". Try \"#[Param] string \$$name\".");
            }

            $typeName = $type->getName();

            self::$cache[$context->key] = $typeName;
        }

        $cname = self::$cache[$context->key];

        $value = $context->requestPathParameters[$name] ?? $value;

        if ("bool" === $cname) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return ok();
    }
}
