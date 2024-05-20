<?php

use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;
use CatPaw\Web\Attributes\Body;
use Preview\Template;

return 
fn (#[Body] array $body) => // The has submitted the form.
    superstyle(asFileName(__DIR__, '../view.hbs'))
        ->withProperties([...$body, 'done' => true]) // Setting properties here.
        ->template(Template::htmx(...))
        ->response();