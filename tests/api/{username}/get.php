<?php

use const CatPaw\Web\__OK;
use const CatPaw\Web\__TEXT_HTML;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;

use CatPaw\Web\Attributes\Summary;

use function CatPaw\Web\success;

return
#[Summary("Get information about an user")]
#[Produces(__OK, __TEXT_HTML, 'string', 'hello world')]
fn (#[Param] string $username) => success("hello $username")->as(__TEXT_HTML);
