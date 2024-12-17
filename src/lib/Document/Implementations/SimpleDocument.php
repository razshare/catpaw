<?php
namespace CatPaw\Document\Implementations;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\error;

use function CatPaw\Core\ok;

use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Document\Render;
use CatPaw\Web\Query;
use ReflectionFunction;
use Throwable;
use WeakMap;

#[Provider(singleton:true)]
class SimpleDocument implements DocumentInterface {
    /** @var array<string,string> */
    private array $aliases = [];
    /** @var WeakMap<object,Render> */
    private WeakMap $cache;

    public function __construct() {
        $this->cache = new WeakMap;
    }

    public function mount(string $fileName): Result {
        try {
            require_once($fileName);

            $functionResult = error("Every document must define a `mount` function, none found in `$fileName`.");

            $definedFunctions = get_defined_functions();
            /** @var array<string> */
            $userDefinedFunctions = $definedFunctions['user'];

            foreach ($userDefinedFunctions as $functionName) {
                if ('mount' === $functionName) {
                    $functionResult = ok($functionName(...));
                }
            }

            $function = $functionResult->unwrap($error);
            if ($error) {
                return error($error);
            }

            if (isset($this->cache[$function])) {
                return ok($this->cache[$function]);
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

            $alias = basename($fileName, '.php');

            $this->aliases[$alias]  = $fileName;
            $this->cache[$function] = $render;

            return ok($render);
        } catch (Throwable $error) {
            return error($error);
        }
    }

    public function render(string $document, array|Query $properties = []):Result {
        $fileName = match (isset($this->aliases[$document])) {
            true  => $this->aliases[$document],
            false => $document
        };
        
        $render = $this->mount($fileName)->unwrap($error);

        if ($error) {
            return error($error);
        }
        
        return $render->run($properties);
    }
}