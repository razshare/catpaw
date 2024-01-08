<?php

namespace CatPaw;

use SplDoublyLinkedList;

readonly class StringStack {
    private function __construct(private string $contents = '') {
    }

    /**
     * Create a string stack from a string.
     * @param  string      $contents
     * @return StringStack
     */
    public static function of(string $contents): StringStack {
        return new self($contents);
    }

    /**
     * Find token within a string and resolve them into a list containing items in the form of <i>[<b>$preceding</b>, <b>$token</b>]</i>,
     * where <b>$token</b> is the matching token and <b>$preceding</b> is the value that $precedes the current token.
     * @param  string              ...$tokens
     * @return SplDoublyLinkedList
     */
    public function expect(string ...$tokens): SplDoublyLinkedList {
        $name  = $this->contents;
        $len   = strlen($name);
        $stack = '';
        $list  = new SplDoublyLinkedList();
        for ($i = 0; $i < $len; $i++) {
            $stack .= $name[$i];
            foreach ($tokens as $token) {
                $length = strlen($token);
                if (str_ends_with($stack, $token)) {
                    $preceding = substr($stack, 0, -$length);
                    $list->push(['' === $preceding ? false: $preceding, $token]);
                    $stack = '';
                    break;
                }
            }
        }

        if ('' !== $stack) {
            $list->push([$stack, false]);
        }

        return $list;
    }
}
