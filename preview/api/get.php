<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;

return fn () => superstyle(asFileName(__DIR__, 'view.hbs'))
    ->setProperty('items', [
        "item-1",
        "item-2",
        "item-3",
        "item-4",
    ])
    ->render();