<?php

namespace CatPaw\Core;

use SplDoublyLinkedList;

readonly class StringStack {
    /**
     * @param string $content
     */
    private function __construct(private string $content = '') {
    }

    /**
     * Create a string stack from a string.
     * @param  string      $content
     * @return StringStack
     */
    public static function of(string $content):StringStack {
        return new self($content);
    }

    /**
     * Find token within a string and resolve them into a list containing items in the form of <i>[<b>$preceding</b>, <b>$token</b>]</i>,
     * where <b>$token</b> is the matching token and <b>$preceding</b> is the value that $precedes the current token.
     * @param  string                                                ...$tokens
     * @return SplDoublyLinkedList<array{false|string,false|string}>
     */
    public function expect(string ...$tokens):SplDoublyLinkedList {
        $name  = $this->content;
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
