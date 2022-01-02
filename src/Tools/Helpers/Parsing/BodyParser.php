<?php

namespace CatPaw\Tools\Helpers\Parsing;

use CatPaw\Tools\Caster;
use CatPaw\Tools\FormData\FormData;
use Exception;
use function json_decode;
use function mb_parse_str;

class BodyParser {
	/**
	 * @throws Exception
	 */
	public static function &parse(string $body, string $contentType, false|string $className = false, bool $toArray = false): mixed {
		if('' === $contentType) {
			throw new Exception("No Content-Type specified. Could not parse body.");
			//return $result;
		} else if($className) {
			if(str_starts_with($contentType, "application/x-www-form-urlencoded")) {
				mb_parse_str($body, $result);
			} else if(str_starts_with($contentType, "application/json")) {
				$result = json_decode($body);
			} else if(str_starts_with($contentType, "multipart/")) {
				[$ok, $result] = FormData::parse($contentType, $body);
			} else {
				echo "No matching Content-Type ($contentType), falling back to null.\n";
				$result = null;
				return $result;
			}
			$result = Caster::cast($result, $className);
			return $result;
		} else if($toArray) try {
			if(str_starts_with($contentType, "application/x-www-form-urlencoded")) {
				mb_parse_str($body, $result);
				return $result;
			} else if(str_starts_with($contentType, "application/json")) {
				$result = json_decode($body, true);
				return $result;
			} else if(str_starts_with($contentType, "multipart/")) {
				[$ok, $result] = FormData::parse($contentType, $body);
				return $result;
			} else {
				echo "No matching Content-Type ($contentType), falling back to empty array.\n";
				$result = [];
				return $result;
			}
		} catch(Exception) {
			echo "Could not convert body to array, falling back to empty array.\n";
			$result = [];
			return $result;
		}
	}
}