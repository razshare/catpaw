<?php
use const CatPaw\Web\__APPLICATION_JSON;
use const CatPaw\Web\__APPLICATION_XML;
use const CatPaw\Web\__OK;
use CatPaw\Web\Accepts;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;
use function CatPaw\Web\badRequest;
use function CatPaw\Web\success;

return
#[Produces(__OK, __APPLICATION_XML, 'on success', 'object')]
#[Produces(__OK, __APPLICATION_JSON, 'on success', 'object')]
static function(Accepts $accepts, #[Param] string $username) {
    $result = [
        'username' => $username,
        'created'  => time(),
        'active'   => true,
    ];
    return match (true) {
        $accepts->json() => success($result)->as(__APPLICATION_JSON),
        $accepts->xml()  => success($result)->as(__APPLICATION_XML),
        default          => badRequest("Invalid content type `$accepts`."),
    };
};
