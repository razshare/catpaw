<?php
namespace CatPaw\Store\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\Interfaces\OnParameterMount;
use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
use CatPaw\Store\Services\StoreService;

use ReflectionParameter;

#[Attribute]
#[Singleton]
class Store implements OnParameterMount {
    use CoreAttributeDefinition;
    private StoreService $storeService;
    
    public function __construct(private readonly string $name) {
    }

    #[Entry] public function setup(StoreService $storeService): void {
        $this->storeService = $storeService;
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        $value = $this->storeService->of($this->name);
        return ok();
    }
}