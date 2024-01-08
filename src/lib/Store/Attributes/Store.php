<?php
namespace CatPaw\Store\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Singleton;
use CatPaw\DependenciesOptions;
use CatPaw\Interfaces\OnParameterMount;
use function CatPaw\ok;
use CatPaw\Store\Services\StoreService;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;

use ReflectionParameter;

#[Attribute]
#[Singleton]
class Store implements OnParameterMount {
    use CoreAttributeDefinition;
    private StoreService $storeService;
    
    public function __construct(private string $name) {
    }

    #[Entry] public function setup(
        StoreService $storeService
    ) {
        $this->storeService = $storeService;
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        $value = $this->storeService->of($this->name);
        return ok();
    }
}