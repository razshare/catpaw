<?php

namespace CatPaw\Tools;

class Caster {
	/**
	 * Cast an \stdClass object to a specific classname.
	 * @param mixed  $obj the object to cast.
	 * @param string $className the name of the class you want to cast the $object as.
	 * @return mixed the newly cast object.
	 */
	public static function cast(array|object $obj, string $className) {
		if(!$obj) return $obj;
		$result = new $className();
		if(\is_array($obj)) {
			foreach($obj as $key => &$value) {
				$result->$key = $value;
			}
		} else {
			$props = \get_object_vars($obj);
			foreach($props as $key => &$value) {
				$result->$key = $value;
			}
		}
		return $result;
	}
}