<?php

namespace CatPaw\Utility;

use JetBrains\PhpStorm\Pure;
use SplDoublyLinkedList;

class StringStack {
	private function __construct(private string $contents = '') { }

	#[Pure] public static function of(string $contents): StringStack {
		return new self($contents);
	}


    /**
     * Find token within a string and resolve them into a list containing items in the form of <i>[<b>$preceeding</b>, <b>$token</b>]</i>,
     * where <b>$token</b> is the matching token and <b>$preceeding</b> is the value that preceeds the current token.
     * @param string ...$tokens
     * @return SplDoublyLinkedList
     */
    public function expect(string ...$tokens): SplDoublyLinkedList {
        $name = $this->contents;
        $len = strlen($name);
        $tknslen = count($tokens);
        $stack = '';
        $list = new SplDoublyLinkedList();

        for($i = 0; $i < $len; $i++) {
            $stack .= $name[$i];
            for($j = 0; $j < $tknslen; $j++) {
                $token = $tokens[$j];
                $tlen = strlen($token);
                if(str_ends_with($stack, $token)) {
                    $preceding = substr($stack, 0, -$tlen);
                    $preceding = '' === $preceding ? false : $preceding??false;

                    $compatible = true;
                    $xstack = '';
                    for($x = 0; $x < $len; $x++) {
                        $xstack .= $name[$x];
                        for($y = 0; $y < $tknslen; $y++) {
                            if($y === $j || strlen($tokens[$y]) < $tlen) continue;
                            $ytoken = $tokens[$y];
                            if(str_ends_with($xstack, $ytoken)) {
                                $compatible = false;
                                break;
                            }
                        }
                        if(!$compatible) break;
                    }

                    if($compatible) {
                        $list->push([$preceding, $token]);
                        $stack = '';
                        break;
                    }
                }
            }
        }

        if('' !== $stack) {
            $list->push([$stack, false]);
        }

        return $list;
    }
}
