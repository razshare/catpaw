<?php
namespace CatPaw\Core;

class ContainerContext {
    /**
     * @param array<callable(ContainerSearchResultItem):(Result<mixed>)> $provided
     * @param mixed                                                      $data
     */
    public function __construct(
        public array $provided = [],
        public mixed $data = false,
    ) {
    }
}
