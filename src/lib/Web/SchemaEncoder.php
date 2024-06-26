<?php
namespace CatPaw\Web;

use CatPaw\Web\Interfaces\OpenApiInterface;
use function count;
use function explode;
use function is_array;

trait SchemaEncoder {
    /**
     *
     * @param  OpenApiInterface $api
     * @param  array<mixed>     $schema
     * @return array<mixed>
     */
    private function unwrap(OpenApiInterface $api, array $schema):array {
        $properties = [];
        $len        = count($schema);
        if (1 === $len && isset($schema[0])) {
            return [
                "type"  => "array",
                "items" => $this->unwrap($api, $schema[0]),
            ];
        }

        foreach ($schema as $key => $type) {
            if (is_array($type)) {
                if (count($type) === 0) {
                    continue;
                }

                if (!isset($type[0])) {
                    $localProperties  = $this->unwrap($api, $type);
                    $properties[$key] = $localProperties;
                    continue;
                }

                $type = $type[0];

                if (is_array($type)) {
                    $properties[$key] = [
                        "type"  => "array",
                        "items" => $this->unwrap($api, $type),
                    ];
                } else {
                    $type             = explode("\\", $type);
                    $type             = $type[count($type) - 1];
                    $properties[$key] = [
                        "type"  => "array",
                        "items" => [
                            "type" => $type,
                        ],
                    ];
                }
            } else {
                $type             = explode("\\", $type);
                $type             = $type[count($type) - 1];
                $properties[$key] = [ "type" => $type ];
            }
        }
        return [
            "type"       => "object",
            "properties" => $properties,
        ];
    }
}
