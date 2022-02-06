<?php

namespace {

	use JetBrains\PhpStorm\Pure;
	use function Examples\Modules\test;

	#[Pure] function main(): string {
		return test();
	}
}