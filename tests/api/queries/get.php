<?php
use CatPaw\Web\Filter;
use function CatPaw\Web\success;
return fn (Filter $filter) => success($filter->join());