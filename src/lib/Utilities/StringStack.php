<?php

namespace CatPaw\Utilities;

use SplDoublyLinkedList;

class StringStack {
    private function __construct(private string $contents = '') {
    }

    public static function of(string $contents): StringStack {
        return new self($contents);
    }

    /**
     * Find token within a string and resolve them into a list containing items in the form of <i>[<b>$preceeding</b>, <b>$token</b>]</i>,
     * where <b>$token</b> is the matching token and <b>$preceeding</b> is the value that preceeds the current token.
     * @param  string              ...$tokens
     * @return SplDoublyLinkedList
     */
    public function expect(string ...$tokens): SplDoublyLinkedList {
        $name = $this->contents;
        $len = strlen($name);
        $stack = '';
        $list = new SplDoublyLinkedList();
        for ($i = 0; $i < $len; $i++) {
            $stack .= $name[$i];
            foreach ($tokens as $token) {
                $tlen = strlen($token);
                if (str_ends_with($stack, $token)) {
                    $precedding = substr($stack, 0, -$tlen);
                    $list->push(['' === $precedding ? false:$precedding ?? false, $token]);
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
