<?php
namespace CatPaw\Superstyle;

use function CatPaw\Core\asFileName;

/**
 * Render twig a file.
 * @param  string                  ...$fileName Path to the twig file.
 * @return SuperstyleRenderContext
 */
function superstyle(string ...$fileName):SuperstyleRenderContext {
    return SuperstyleRenderContext::create(asFileName(...$fileName));
}
