<?php
namespace CatPaw\Environment\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Environment\Services\EnvironmentService;

use ReflectionParameter;

#[Attribute]
class Environment implements AttributeInterface {
    use CoreAttributeDefinition;

    private ?EnvironmentService $environmentService = null;

    public function __construct(
        private string $variableName
    ) {
    }

    #[Entry] public function setup(
        EnvironmentService $environmentService
    ):void {
        $this->environmentService = $environmentService;
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        if (!$this->environmentService) {
            return;
        }
        $variables = $this->environmentService->getVariables();
        $value     = $variables[$this->variableName] ?? $value ?? null;
    }
}