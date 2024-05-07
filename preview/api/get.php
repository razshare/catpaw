<?php

use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;
use Preview\Template;


return fn () => // The user just landed on the main page, 
                // provide them with a form to submit.
        superstyle(asFileName(__DIR__, './view.hbs'))
            ->template(Template::htmx(...))
            ->render();