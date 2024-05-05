<?php

use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;
use CatPaw\Web\Attributes\Body;

return function(#[Body] array $body) {
    return superstyle(asFileName(__DIR__, '../view.hbs'))->setProperties([
        ...$body,
        'done' => true,
    ])->template(htmx(...))->render();
};