<?php

namespace CatPaw\Web\Attributes;

use Amp\Http\Server\RequestBody;
use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use function CatPaw\Core\ok;

use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
use CatPaw\Web\BodyParser;
use CatPaw\Web\RequestContext;
use ReflectionParameter;
use Throwable;

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

        try {
            $attempt = match ($className) {
                "string" => $context->request->getBody()->buffer(),

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
        } catch(Throwable $e) {
            return error($e);
        }

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
        return BodyParser::parse($body, $contentType);
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
     * @param  string $body
     * @return bool
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
