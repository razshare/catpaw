<?php

use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Summary;

use const CatPaw\Web\TEXT_HTML;

return 
#[Summary("Get information about an user")]
#[Produces('string', TEXT_HTML)]
fn (#[Param] string $username) => "hello $username";