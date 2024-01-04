<?php

namespace CatPaw\Web;

use function count;
use function preg_match;
use function preg_replace;
use function preg_split;

class FormParser {
    private function __construct() {
    }
    public static function parse(string $ctype, string $input): object|array {
        // grab multipart boundary from content type header
        if (!preg_match('/boundary=(.*)$/', $ctype, $matches)) {
            return [false, []];
        }

        $boundary = $matches[1] ?? '';

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        if (count($a_blocks) === 0) {
            return [false, []];
        }
        $entries = [];
        // loop data blocks
        foreach ($a_blocks as &$block) {
            if (empty($block)) {
                continue;
            }

            $entry = [
                'attributes'  => '',
                'contentType' => '',
                'body'        => '',
            ];

            if (($pieces = preg_split('/\r\n\r\n/', $block, 2)) && count($pieces) > 1) {
                $block  = null;
                $header = preg_replace('/(?<=^)\s*/', '', $pieces[0]);
                $body   = preg_replace('/\r?\n(?=$)/', '', $pieces[1]);
                $pieces = null;
                $lines  = preg_split('/(\r?\n)+/', $header);
                $header = null;

                foreach ($lines as &$line) {
                    if (preg_match('/(?<=^Content-Disposition:).+/', $line, $attrs)) {
                        $attrs      = preg_split('/;\s+/', $attrs[0] ?? '');
                        $attrs_len  = count($attrs);
                        $attributes = [];
                        for ($i = 0;$i < $attrs_len;$i++) {
                            $attrs[$i] = preg_replace('/^\s+/', '', $attrs[$i]);
                            if (preg_match('/(.+)(\=\")(.+)(\")/', $attrs[$i], $pair)) {
                                $attributes[$pair[1] ?? ''] = $pair[3] ?? '';
                            }
                        }
                        $entry['attributes'] = &$attributes;
                    } elseif (preg_match('/(?<=^Content-Type:).+/', $line, $ct)) {
                        $contentType          = preg_replace('/^\s+/', '', $ct[0] ?? '');
                        $entry['contentType'] = &$contentType;
                    }
                }
                if (isset($entry['attributes']['name'])) {
                    $entry['body']                         = $body;
                    $body                                  = null;
                    $entries[$entry['attributes']['name']] = $entry;
                    $entry                                 = null;
                }
            }
        }
        return [true, $entries];
    }
}
