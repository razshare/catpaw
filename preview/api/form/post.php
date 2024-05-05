<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Superstyle\superstyle;

return fn () => superstyle(asFileName(__DIR__, '../view.hbs'))->setProperty("done", true)->template(htmx(...))->render();