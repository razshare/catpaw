<?php

namespace CatPaw\Document;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Web\Body;
use CatPaw\Web\Query;
use ReflectionParameter;
use Throwable;

class Render {
    /**
     * 
     * @param mixed                      $function
     * @param array<ReflectionParameter> $reflectionParameters
     */
    public function __construct(
        private mixed $function,
        private array $reflectionParameters,
    ) {
    }

    /**
     * 
     * @param  array<string|int,mixed>|Query|Body $properties
     * @return Result<string>
     */
    public function run(array|Query|Body $properties) {
        try {
            $parameters = [];
            if ($properties instanceof Query) {
                /** @var Query $properties */
                $items = $properties->all();

                foreach ($this->reflectionParameters as $reflectionParameter) {
                    $type = ReflectionTypeManager::unwrap($reflectionParameter);
                    $name = $reflectionParameter->getName();

                    if (!isset($items[$name])) {
                        if ($reflectionParameter->isDefaultValueAvailable()) {
                            $parameters[] = $reflectionParameter->getDefaultValue();
                            continue;
                        }
                        $parameters[] = match ($type->getName()) {
                            'bool'   => false,
                            'int'    => 0,
                            'float'  => 0.0,
                            'string' => '',
                            default  => false,
                        };
                        continue;
                    }

                    $value = $items[$name];

                    $parameters[] = match ($type->getName()) {
                        'bool'  => $value->bool(),
                        'int'   => $value->int(),
                        'float' => $value->float(),
                        default => $value->text(),
                    };
                }
            } else if ($properties instanceof Body) {
                $properties = (array)$properties->object()->unwrap($error);
                if ($error) {
                    return error($error);
                }
                foreach ($this->reflectionParameters as $reflectionParameter) {
                    $name         = $reflectionParameter->getName();
                    $parameters[] = $properties[$name];
                }
            } else if (array_is_list($properties)) {
                $parameters = $properties;
            } else {
                foreach ($this->reflectionParameters as $reflectionParameter) {
                    $name         = $reflectionParameter->getName();
                    $parameters[] = $properties[$name];
                }
            }

            ob_start();
            $result = ($this->function)(...$parameters);
            if (null === $result) {
                $result = ob_get_contents()?:'';
            }
            ob_end_clean();

            if ($result instanceof Result) {
                $content = $result->unwrap($error);
                if ($error) {
                    return error($error);
                }
                return ok($content);
            }

            return ok($result);
        } catch(Throwable $error) {
            return error($error);
        }
    }
}