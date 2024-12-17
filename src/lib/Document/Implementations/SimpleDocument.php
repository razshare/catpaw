<?php
namespace CatPaw\Document\Implementations;

use CatPaw\Core\Attributes\Provider;

use function CatPaw\Core\error;


use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;

use CatPaw\Document\Interfaces\DocumentInterface;

use CatPaw\Document\Render;
use function CatPaw\Web\failure;

use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Query;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;
use ReflectionFunction;

use Throwable;


#[Provider(singleton:true)]
class SimpleDocument implements DocumentInterface {
    /** @var array<string,string> */
    private array $aliases = [];
    /** @var array<string,Render> */
    private array $cache = [];

    public function mount(string $fileName): Result {
        try {
            if (!$function = require_once($fileName)) {
                return error("A document must always return a function, non detected in `$fileName`.");
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

            $alias = basename($fileName, '.php');

            $this->aliases[$alias]  = $fileName;
            $this->cache[$fileName] = $render;

            return ok($render);
        } catch (Throwable $error) {
            return error($error);
        }
    }

    public function render(string $documentName, array|Query $properties = []):ResponseModifier {
        $fileName = match (isset($this->aliases[$documentName])) {
            true  => $this->aliases[$documentName],
            false => $documentName
        };
        
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