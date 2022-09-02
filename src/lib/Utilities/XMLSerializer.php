<?php

namespace CatPaw\Utilities;

use function get_object_vars;
use stdClass;

class XMLSerializer {
    private function __construct() {
    }

    /**
     * Generate xml string from object.
     * @return string xml string.
     */
    public static function generateValidXmlFromObj(stdClass $obj, string $node_block = 'nodes', string $node_name = 'node'): string {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    /**
     * Generate an xml string from array.
     * @return string xml string.
     */
    public static function generateValidXmlFromArray(mixed $array, string $node_block = 'nodes', string $node_name = 'node'): string {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<'.$node_block.'>';
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</'.$node_block.'>';

        return $xml;
    }
    
    private static function generateXmlFromArray(mixed $array, string $node_name): string {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key => $value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<'.$key.'>'.self::generateXmlFromArray($value, $node_name).'</'.$key.'>';
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }
}