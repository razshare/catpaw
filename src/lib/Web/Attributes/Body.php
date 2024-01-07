<?php

namespace CatPaw\Web\Attributes;

use Amp\Http\Server\RequestBody;
use Attribute;
use CatPaw\DependenciesOptions;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Interfaces\OnParameterMount;

use function CatPaw\ok;
use CatPaw\ReflectionTypeManager;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use CatPaw\Web\BodyParser;
use CatPaw\Web\RequestContext;
use ReflectionParameter;

/**
 * Get the body of the request.
 * 
 * - `#[Body] int $raw` 
 * - `#[Body] float $raw`
 * - `#[Body] string $raw`
 * - `#[Body] array $data`
 * - `#[Body] MyClass $account`
 * - `#[Body] \Amp\Http\Server\FormParser\Form $files`
 * 
 * @see Consumes
 * @package CatPaw\Web\Attributes
 */
#[Attribute]
class Body implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var false|RequestContext $context */
        $context = $options->context;

        if (!$context) {
            return error("No context provided for body.");
        }
        
        $className = ReflectionTypeManager::unwrap($reflection)?->getName() ?? '';
        
        $attempt = match ($className) {
            "array" => $this->toArray(
                body       : $context->request->getBody(),
                contentType: $context->request->getHeader("Content-Type") ?? '',
            ),

            "string" => $context->request->getBody(),

            "int" => $this->toInteger(
                body: $context->request->getBody(),
            ),

            "bool" => $this->toBool(
                body: $context->request->getBody(),
            ),

            "float" => $this->toFloat(
                body: $context->request->getBody(),
            ),
            
            RequestBody::class => $context->request->getBody(),

            default => $this->toArray(
                body       : $context->request->getBody(),
                contentType: $context->request->getHeader("Content-Type") ?? '',
            ),
        };

        if ($attempt->error) {
            return error($attempt->error);
        }

        $value = $attempt->value;

        return ok();
    }

    /**
     * @@return Unsafe<mixed>
     */
    private function toArray(string $body, string $contentType): Unsafe {
        return BodyParser::parse(
            $body,
            $contentType,
            false,
            true,
        );
    }

    /**
     * @@return Unsafe<int>
     */
    private function toInteger(string $body):Unsafe {
        if (is_numeric($body)) {
            return ok((int)$body);
        } else {
            return error('Body was expected to be numeric (int), but non numeric value has been provided instead:'.$body);
        }
    }


    /**
     * @@return bool
     */
    private function toBool(string $body):bool {
        return filter_var($body, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @@return Unsafe<float>
     */
    private function toFloat(string $body):Unsafe {
        if (is_numeric($body)) {
            return ok((float)$body);
        } else {
            return error('Body was expected to be numeric (float), but non numeric value has been provided instead:'.$body);
        }
    }
}
