<?php
use CatPaw\Web\Attributes\Produces;
use const CatPaw\Web\OK;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_PLAIN;

return #[Produces(OK, TEXT_PLAIN, 'on success', 'string')] static fn () => success('root');
