<?php

namespace CatPaw;

use Generator;

interface SetupGeneratorInterface {
    public function setup(): Generator;
}