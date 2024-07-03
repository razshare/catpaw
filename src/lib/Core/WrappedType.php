<?php
namespace CatPaw\Core;

readonly class WrappedType {
    /**
     * @param bool   $allowsBoolean
     * @param bool   $allowsTrue
     * @param bool   $allowsFalse
     * @param bool   $allowsNullValue
     * @param bool   $allowsDefaultValue
     * @param mixed  $defaultValue
     * @param string $className
     */
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

    /**
     * @return bool
     */
    public function allowsBoolean():bool {
        return $this->allowsBoolean;
    }
    
    /**
     * @return bool
     */
    public function allowsTrue():bool {
        return $this->allowsTrue;
    }
    
    /**
     * @return bool
     */
    public function allowsFalse():bool {
        return $this->allowsFalse;
    }
    
    /**
     * @return bool
     */
    public function allowsNullValue():bool {
        return $this->allowsNullValue;
    }
    
    /**
     * @return bool
     */
    public function allowsDefaultValue():bool {
        return $this->allowsDefaultValue;
    }
    
    /**
     * @return mixed
     */
    public function defaultValue():mixed {
        return $this->defaultValue;
    }
    
    /**
     * @return string
     */
    public function className():string {
        return $this->className;
    }
}