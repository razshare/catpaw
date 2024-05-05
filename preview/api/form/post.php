<?php

use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;
use CatPaw\Web\Attributes\Body;

return 
fn (#[Body] array $body) => // The has submitted the form.
    superstyle(asFileName(__DIR__, '../view.hbs'))
        ->setProperties([...$body, 'done' => true])
        ->template(htmx(...))
        ->render();