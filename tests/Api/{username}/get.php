<?php

use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Summary;
use const CatPaw\Web\OK;

use function CatPaw\Web\success;

use const CatPaw\Web\TEXT_HTML;

return
#[Summary("Get information about an user")]
#[Produces(OK, TEXT_HTML, 'on success', 'string', 'hello world')]
fn (#[Param] string $username) => success("hello $username")->as(TEXT_HTML);
