<?php

namespace CatPaw\Configs;

use Generator;

interface SetupGeneratorInterface {
	public function setup(): Generator;
}