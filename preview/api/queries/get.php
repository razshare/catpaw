<?php
use const CatPaw\Web\APPLICATION_JSON;

use CatPaw\Web\Filter;

use function CatPaw\Web\success;
return fn (Filter $filter) => success([
    'template'   => $filter->join(),
    'properties' => $filter->getProperties(),
])->as(APPLICATION_JSON);