<?php
namespace CatPaw\Document\Implementations;

use CatPaw\Core\Attributes\Provider;

use function CatPaw\Core\error;


use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;

use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Document\MountContext;
use CatPaw\Document\Render;
use CatPaw\Web\Body;

use function CatPaw\Web\failure;

use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Query;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;
use ReflectionFunction;

use Throwable;


#[Provider(singleton:true)]
class SimpleDocument implements DocumentInterface {
    /** @var array<string,Render> */
    private array $cache = [];

    public function mount(string $fileName, false|callable $onLoad = false): Result {
        try {
            $initialDefinedVariables = get_defined_vars();
            $initialDefinedFunctions = get_defined_functions()['user'];
            $initialDefinedConstants = get_defined_constants();
            
            if (!$function = require_once($fileName)) {
                return error("A document must always return a function, non detected in `$fileName`.");
            }

            $finalDefinedVariables = get_defined_vars();
            $finalDefinedFunctions = get_defined_functions()['user'];
            $finalDefinedConstants = get_defined_constants();

            if ($onLoad) {
                $functions = [];
                $variables = [];
                $constants = [];

                foreach ($finalDefinedFunctions as $functionName) {
                    if (!in_array($functionName, $initialDefinedFunctions)) {
                        $functions[$functionName] = $functionName(...);
                    }
                }

                foreach ($finalDefinedVariables as $key => $value) {
                    if (in_array($key, [
                        'initialDefinedVariables',
                        'initialDefinedFunctions',
                        'initialDefinedConstants',
                        'function',
                        'fileName',
                    ])) {
                        continue;
                    }
                    if (!isset($initialDefinedVariables[$key])) {
                        $variables[$key] = $value;
                    }
                }

                foreach ($finalDefinedConstants as $key => $value) {
                    if (in_array($key, [
                        'NULL',
                    ])) {
                        continue;
                    }
                    if (!isset($initialDefinedConstants[$key])) {
                        $constants[$key] = $value;
                    }
                }

                $context = new MountContext(
                    fileName: $fileName,
                    functions: $functions,
                    variables: $variables,
                    constants: $constants,
                    mountFunction: $function,
                );

                $onLoad($context)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }

            if (isset($this->cache[$fileName])) {
                return ok($this->cache[$fileName]);
            }

            $reflectionFunction = new ReflectionFunction($function);
            $complexReturnType  = $reflectionFunction->getReturnType();

            if (null !== $complexReturnType) {
                $returnType     = ReflectionTypeManager::unwrap($complexReturnType);
                $returnTypeName = $returnType->getName();
                $void           = 'void' === $returnTypeName;
                if (!$void) {
                    return error("Document functions must always return `void`.");
                }
            }


            $render = new Render(
                function: $function,
                reflectionParameters: $reflectionFunction->getParameters(),
            );

            $this->cache[$fileName] = $render;

            return ok($render);
        } catch (Throwable $error) {
            return error($error);
        }
    }

    public function render(string $fileName, array|Query|Body $properties = []):ResponseModifier {
        $render = $this->mount($fileName)->unwrap($error);

        if ($error) {
            return failure($error->getMessage())->as(TEXT_HTML);
        }
        
        $content = $render->run($properties)->unwrap($error);

        if ($error) {
            return failure($error->getMessage())->as(TEXT_HTML);
        }

        return success($content)->as(TEXT_HTML);
    }
}