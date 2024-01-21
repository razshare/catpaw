<?php
use CatPaw\Web\Attributes\Produces;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_PLAIN;

return #[Produces('string', TEXT_PLAIN)] static fn () => success('hello');
