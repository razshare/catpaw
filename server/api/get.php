<?php
use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Query;

return #[Produces('string', APPLICATION_JSON)] fn (#[Query] string $username) => "hello $username";