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
     * @param  string                   $globals
     * @return SuperstyleExecutorResult
     */
    public function withGlobals(string $globals):self {
        $this->globals = $globals;
        return $this;
    }

    /**
     * @return string
     */
    public function getGlobals():string {
        return $this->globals;
    }
}
