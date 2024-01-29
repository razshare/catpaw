<?php
use CatPaw\Web\Accepts;
use const CatPaw\Web\APPLICATION_JSON;
use const CatPaw\Web\APPLICATION_XML;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Attributes\Produces;
use function CatPaw\Web\badRequest;
use const CatPaw\Web\OK;
use function CatPaw\Web\success;

return
#[Produces(OK, APPLICATION_XML, 'on success', 'object')]
#[Produces(OK, APPLICATION_JSON, 'on success', 'object')]
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
