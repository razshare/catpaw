<?php
namespace CatPaw\Document\Implementations;

use CatPaw\Core\Attributes\Provider;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Document\DocumentPropertyHint;
use CatPaw\Document\DocumentRunConfiguration;

use CatPaw\Document\Interfaces\DocumentInterface;
use Throwable;

#[Provider(singleton:true)]
class SimpleDocument implements DocumentInterface {
    /** @var array<string,DocumentRunConfiguration> */
    private array $documents = [];
    /** @var array<string,string> */
    private array $aliases = [];

    public function mount(string $path): Result {
        try {
            $config  = $GLOBALS[DocumentRunConfiguration::class] = DocumentRunConfiguration::create($path);
            $initial = [...get_defined_vars()];
            ob_start();
            require($path);
            ob_get_contents()?:'';
            ob_end_clean();
            $final = [...get_defined_vars()];

            foreach ($final as $key => $value) {
                if ('initial' === $key || 'fileName' === $key) {
                    continue;
                }

                if (!isset($initial[$key]) && $value instanceof DocumentPropertyHint) {
                    $config->propertyHints[$key] = $value;
                }
            }

            $this->documents[$path] = $config;
            if ('' !== $config->documentName) {
                $this->aliases[$config->documentName] = $path;
            }

            $config->mounted = true;

            return ok();
        } catch (Throwable $error) {
            return error($error);
        }
    }

    public function run(string $document, array $properties = []):Result {
        if (isset($this->aliases[$document])) {
            $document = $this->aliases[$document];
        }

        if (!isset($this->documents[$document])) {
            $this->mount($document)->unwrap($error);
            if ($error) {
                return error($error);
            }
            return $this->run($document, $properties);
        }

        try {
            foreach ($this->documents[$document]->propertyHints as $key => $type) {
                $$key = match ($type) {
                    'int'    => (int)$properties[$key],
                    'float'  => (float)$properties[$key],
                    'bool'   => (bool)$properties[$key],
                    'string' => (bool)$properties[$key],
                    default  => $properties[$key],
                };
            }

            ob_start();
            unset($properties);
            require($document);
            $result = ob_get_contents()?:'';
            ob_end_clean();
            return ok($result);
        } catch(Throwable $error) {
            return error($error);
        }
    }
}