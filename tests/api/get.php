<?php

use CatPaw\Web\Attributes\Produces;

use const CatPaw\Web\TEXT_PLAIN;

return #[Produces('string', TEXT_PLAIN)] function() {
    return 'hello';
};