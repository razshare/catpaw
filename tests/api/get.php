<?php
use const CatPaw\Web\__OK;
use const CatPaw\Web\__TEXT_PLAIN;
use CatPaw\Web\Attributes\Produces;
use function CatPaw\Web\success;

return #[Produces(__OK, __TEXT_PLAIN, 'on success', 'string')] static fn () => success('hello');
