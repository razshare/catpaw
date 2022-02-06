<?php

namespace CatPaw\Attribute\Metadata;

class Meta {
	public static array $FUNCTION = [];

	public static function reset(): void {
		static::$FUNCTION = [];
	}
}