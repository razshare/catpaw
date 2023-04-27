<?php
namespace CatPaw\Utilities;

class WrappedType {
    public function __construct(
        private bool $allowsBoolean,
        private bool $allowsTrue,
        private bool $allowsFalse,
        private bool $allowsNullValue,
        private bool $allowsDefaultValue,
        private mixed $defaultValue,
        private string $className,
    ) {
    }

    public function allowsBoolean():bool {
        return $this->allowsBoolean;
    }
    public function allowsTrue():bool {
        return $this->allowsTrue;
    }
    public function allowsFalse():bool {
        return $this->allowsFalse;
    }
    public function allowsNullValue():bool {
        return $this->allowsNullValue;
    }
    public function allowsDefaultValue():bool {
        return $this->allowsDefaultValue;
    }
    public function getDefaultValue():mixed {
        return $this->defaultValue;
    }
    public function getClassName():string {
        return $this->className;
    }
}