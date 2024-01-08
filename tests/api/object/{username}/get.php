<?php
use const CatPaw\Web\APPLICATION_JSON;
use const CatPaw\Web\APPLICATION_XML;

use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;

return
#[Produces('object', [APPLICATION_JSON, APPLICATION_XML])]
function(#[Param] string $username) {
    return [
        'username' => $username,
        'created'  => time(),
        'active'   => true,
    ];
};
