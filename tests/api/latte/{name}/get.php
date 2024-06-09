<?php
use function CatPaw\Web\view;
return fn (string $name) => view()->withProperty('name', $name);