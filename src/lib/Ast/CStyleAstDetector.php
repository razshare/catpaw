<?php

namespace CatPaw\Ast;

use CatPaw\Ast\Interfaces\AstSearchInterface;
use CatPaw\Ast\Interfaces\CStyleDetectorInterface;
use function CatPaw\Core\anyError;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;

class CStyleAstDetector implements AstSearchInterface {
    /**
     *
     * @param  string                    $fileName
     * @return Unsafe<CStyleAstDetector>
     */
    public static function fromFile(string $fileName): Unsafe {
        return anyError(function() use ($fileName) {
            $file   = File::open($fileName)->try();
            $source = $file->readAll()->try();
            return self::fromSource($source);
        });
    }

    public static function fromSource(string $source): self {
        return new self(source: $source);
    }

    private string $previousSource;

    private function __construct(
        public string $source,
    ) {
        $this->previousSource = $this->source;
    }

    public function resetToPreviousSource():void {
        $this->source = $this->previousSource;
    }

    // /**
    //  * Search the next occurrence of `$tokens`.
    //  * @param  string             ...$tokens
    //  * @return false|SearchResult
    //  */
    // public function next(string ...$tokens): false|SearchResult {
    //     $length = strlen($this->source);
    //     $before = '';
    //     for ($i = 0; $i < $length; $i++) {
    //         $before .= $this->source[$i];
    //         foreach ($tokens as $token) {
    //             if (str_ends_with($before, $token)) {
    //                 $this->previousSource = $this->source;
    //                 $this->source         = substr($this->source, $i + 1);
    //                 return new SearchResult(
    //                     token : $token,
    //                     before: substr($before, 0, -strlen($token)),
    //                 );
    //             }
    //         }
    //     }
    //     return false;
    // }

    /**
     * Search the next occurrence of `$tokens`.
     * @param  string             ...$tokens
     * @return false|SearchResult
     */
    public function next(string ...$tokens): false|SearchResult {
        $length        = strlen($this->source);
        $before        = '';
        $token_lengths = [];
        foreach ($tokens as $token) {
            $token_lengths[] = strlen($token);
        }

        for ($i = 0; $i < $length; $i++) {
            $before .= $this->source[$i];
            
            foreach ($tokens as $index => $token) {
                if ($token_lengths[$index] > 1) {
                    if (str_ends_with($before, $token[0])) {
                        $localBefore = $before;
                        for ($j = $i + 1, $il = 2; $j < $length; $j++,$il++) {
                            $localBefore .= $this->source[$j];
                            $chunk = substr($token, 0, $il);
                            if (str_ends_with($localBefore, $chunk)) {
                                if ($chunk !== $token) {
                                    continue;
                                }
                                $this->previousSource = $this->source;
                                $this->source         = substr($this->source, $j + 1);
                                return new SearchResult(
                                    token : $token,
                                    before: substr($localBefore, 0, -strlen($token)),
                                );
                            }
                            break;
                        }
                        continue;
                    }
                    continue;
                }

                if (str_ends_with($before, $token)) {
                    $this->previousSource = $this->source;
                    $this->source         = substr($this->source, $i + 1);
                    return new SearchResult(
                        token : $token,
                        before: substr($before, 0, -strlen($token)),
                    );
                }
            }
        }
        return false;
    }

    /**
     * Parse source code as C style.
     * @param  CStyleDetectorInterface $detector
     * @param  false|Block             $parent
     * @param  int                     $depth
     * @return Unsafe<None>
     */
    public function detect(
        CStyleDetectorInterface $detector,
        false|Block $parent = false,
        int $depth = 0,
    ): Unsafe {
        $uncommented = '';
        $search      = CStyleAstDetector::fromSource(preg_replace('/^\s*\/\/.*/m', '', $this->source));

        $comment_opened = 0;
        $comment_closed = 0;
        while (true) {
            if (!$result = $search->next('/*', '*/')) {
                if ($comment_opened === $comment_closed) {
                    $uncommented .= $search->source;
                }
                break;
            }

            if ('/*' === $result->token && $comment_opened === $comment_closed) {
                $uncommented .= $result->before;
                $comment_opened++;
            } else if ('*/' === $result->token) {
                $comment_closed++;
                if ($comment_closed > $comment_opened) {
                    return ok();
                }
            }
        }

        $search = CStyleAstDetector::fromSource($uncommented);

        $name                 = '';
        $opened_braces        = 0;
        $closed_braces        = 0;
        $block_found          = false;
        $opened_brackets      = 0;
        $closed_brackets      = 0;
        $opened_double_braces = 0;
        $closed_double_braces = 0;
        $double_quotes        = 0;
        $single_quotes        = 0;
        $escaped_character    = false;
        $body                 = '';
        $rules                = [];
        $missed               = '';

        $reset = function() use (
            &$name,
            &$opened_braces,
            &$closed_braces,
            &$block_found,
            &$opened_brackets,
            &$closed_brackets,
            &$opened_double_braces,
            &$closed_double_braces,
            &$double_quotes,
            &$single_quotes,
            &$escaped_character,
            &$body,
            &$rules,
            &$missed,
        ) {
            $name                 = '';
            $opened_braces        = 0;
            $closed_braces        = 0;
            $block_found          = false;
            $opened_brackets      = 0;
            $closed_brackets      = 0;
            $opened_double_braces = 0;
            $closed_double_braces = 0;
            $double_quotes        = 0;
            $single_quotes        = 0;
            $escaped_character    = false;
            $body                 = '';
            $rules                = [];
            $missed               = '';
        };

        while (true) {
            if ($block_found) {
                if (!$result = $search->next('{{', '}}', '{', '}')) {
                    return ok();
                }

                if ('}' === $result->token) {
                    $closed_braces++;

                    if ($opened_braces !== $closed_braces) {
                        $missed .= $result->before.$result->token;
                        continue;
                    }

                    $body .= $missed.$result->before;
                    $missed       = '';
                    $this->source = $search->source;
                    $block        = new Block(
                        signature: $name,
                        body     : trim($body),
                        rules    : $rules,
                        parent   : $parent,
                        depth    : $depth,
                    );
    
                    if ($parent) {
                        $parent->children[] = $block;
                    }
    
                    if ($block->body) {
                        $searchLocal = CStyleAstDetector::fromSource($block->body);
                        $searchLocal->detect($detector, $block, $depth + 1);
                    }

                    $detector->onBlock($block, $depth + 1);
    
                    $reset();

                    continue;
                }


                if ('{' === $result->token) {
                    $opened_braces++;
                }

                $missed .= $result->before.$result->token;
                continue;
            }

            if (!$result = $search->next('{{', '}}', '[', ']', '"', '\'', '\\', '{', '}', ';')) {
                return ok();
            }

            if ('[' === $result->token) {
                $opened_brackets++;
                $missed .= $result->before.$result->token;
                continue;
            } else if (']' === $result->token) {
                $closed_brackets++;
                $missed .= $result->before.$result->token;
                continue;
            } else if ($opened_brackets !== $closed_brackets) {
                $missed .= $result->before.$result->token;
                continue;
            }

            if ('{{' === $result->token) {
                $opened_double_braces++;
                $missed .= $result->before.$result->token;
                continue;
            } else if ('}}' === $result->token) {
                $closed_double_braces++;
                $missed .= $result->before.$result->token;
                continue;
            } else if ($opened_double_braces !== $closed_double_braces) {
                $missed .= $result->before.$result->token;
                continue;
            }

            if ('"' === $result->token && 0 === $single_quotes % 2) {
                $missed .= $result->before.$result->token;
                if (!$escaped_character) {
                    $double_quotes++;
                } else {
                    $escaped_character = false;
                }
                continue;
            }

            if ('\'' === $result->token && 0 === $double_quotes % 2) {
                $missed .= $result->before.$result->token;
                if (!$escaped_character) {
                    $single_quotes++;
                } else {
                    $escaped_character = false;
                }
                continue;
            }

            if (0 !== $double_quotes % 2 && 0 !== $single_quotes) {
                if ('\\' === $result->token) {
                    $escaped_character = true;
                } else {
                    $escaped_character = false;
                }
                $missed .= $result->before.$result->token;
                continue;
            }

            if (';' === $result->token) {
                $rule   = trim($missed.$result->before);
                $missed = '';
                if ('' === $rule) {
                    continue;
                }
                if ($parent) {
                    $parent->rules[] = $rule;
                }
                $detector->onRule($parent, $rule)->unwrap($error);
                if ($error) {
                    return error($error);
                }
                continue;
            }

            if ('{' === $result->token) {
                $name   = trim($missed.$result->before);
                $missed = '';
                $opened_braces++;
                $block_found = true;
                continue;
            }

            if ('"' === $result->token) {
                $double_quotes++;
            }

            if ('\'' === $result->token) {
                $single_quotes++;
            }

            $body .= $missed.$result->before.$result
                    ->token;
        }
    }
}
