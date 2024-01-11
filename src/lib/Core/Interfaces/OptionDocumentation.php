<?php
namespace CatPaw\Core\Interfaces;

abstract class OptionDocumentation {
    /**
     * An example to show on the page.
     * @var string
     */
    public string $example;
    /**
     * A description to show on the page.
     * @var string
     */
    public string $description;
}