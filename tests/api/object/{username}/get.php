<?php
use CatPaw\Web\Accepts;
use const CatPaw\Web\APPLICATION_JSON;
use const CatPaw\Web\APPLICATION_XML;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;
use function CatPaw\Web\badRequest;
use function CatPaw\Web\success;

return
#[Produces('object', [APPLICATION_JSON, APPLICATION_XML])]
static function(Accepts $accepts, #[Param] string $username) {
    $result = [
        'username' => $username,
        'created'  => time(),
        'active'   => true,
    ];
    return match (true) {
        $accepts->json() => success($result)->as(APPLICATION_JSON),
        $accepts->xml()  => success($result)->as(APPLICATION_XML),
        default          => badRequest("Invalid content type `$accepts`."),
    };
};
