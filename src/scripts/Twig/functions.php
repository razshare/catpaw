<?php
namespace CatPaw\Twig;

/**
 * Render a twig component.
 * @param  string            $name The component name.
 * @return TwigRenderContext
 */
function twig(string $name):TwigRenderContext {
    return new TwigRenderContext($name);
}