<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;

return fn () => // The user just landed on the main form page, 
                // provide them with a form to submit.
        superstyle(asFileName(__DIR__, './view.hbs'))
            ->setProperty("items", [
                "item-1",
                "item-2",
                "item-3",
            ])
            ->template(htmx(...))
            ->render();