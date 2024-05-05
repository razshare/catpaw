<?php
namespace CatPaw\Superstyle;

class SuperstyleExecutorResult {
    /** @var string */
    private string $globals = '';

    public function __construct(
        public readonly string $html,
        public readonly string $css,
    ) {
    }

    /**
     * 
     * @param  array<string>            $globals
     * @return SuperstyleExecutorResult
     */
    public function withGlobals(array $globals):self {
        $this->globals = join($globals);
        return $this;
    }

    /**
     * @return string
     */
    public function getGlobals():string {
        return $this->globals;
    }
}
