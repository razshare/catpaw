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
     * Same as `next()`, but it ignores double quotes, single quotes and the different permutations taking into account `\` as an escape character.
     * @param  string             ...$tokens
     * @return false|SearchResult
     */
    public function nextIgnoreQuotes(string ...$tokens):false|SearchResult {
        $escaped_character = false;
        $double_quotes     = 0;
        $single_quotes     = 0;
        $before            = '';
        $escaped_character = false;
        
        while (true) {
            $result = $this->next('\\', '"', '\'', ...$tokens);
            if (!$result) {
                return false;
            }

            // @phpstan-ignore-next-line
            $reading_string = 0 !== ($double_quotes % 2) || 0 !== ($single_quotes % 2);

            if ($reading_string) {
                if ($escaped_character) {
                    $before .= $result->before.$result->token;
                    $escaped_character = false;
                    continue;
                }

                if ('\\' === $result->token) {
                    $escaped_character = true;
                    continue;
                }
            }

            if ('"' === $result->token) {
                $before .= $result->before.$result->token;
                $double_quotes++;
                continue;
            }

            if ('\'' === $result->token) {
                $before .= $result->before.$result->token;
                $double_quotes++;
                continue;
            }

            if ($reading_string) {
                $before .= $result->before.$result->token;
                continue;
            }

            return new SearchResult($result->token ?? '', $before.$result->before);
        }
    }

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
        bool $clearComments = true,
    ): Unsafe {
        if ($clearComments) {
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
        } else {
            $search = CStyleAstDetector::fromSource($this->source);
        }


        $name              = '';
        $opened_curly      = 0;
        $closed_curly      = 0;
        $block_found       = false;
        $opened_square     = 0;
        $closed_square     = 0;
        $double_quotes     = 0;
        $single_quotes     = 0;
        $escaped_character = false;
        $body              = '';
        $rules             = [];
        $missed            = '';

        $reset = function() use (
            &$name,
            &$opened_curly,
            &$closed_curly,
            &$block_found,
            &$opened_square,
            &$closed_square,
            &$double_quotes,
            &$single_quotes,
            &$escaped_character,
            &$body,
            &$rules,
            &$missed,
        ) {
            $name              = '';
            $opened_curly      = 0;
            $closed_curly      = 0;
            $block_found       = false;
            $opened_square     = 0;
            $closed_square     = 0;
            $double_quotes     = 0;
            $single_quotes     = 0;
            $escaped_character = false;
            $body              = '';
            $rules             = [];
            $missed            = '';
        };

        while (true) {
            if ($block_found) {
                if (!$result = $search->nextIgnoreQuotes('{', '}')) {
                    return ok();
                }

                if ('}' === $result->token) {
                    $closed_curly++;

                    if ($opened_curly !== $closed_curly) {
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
                        $searchLocal->detect(detector:$detector, parent:$block, depth:$depth + 1, clearComments:false);
                    }

                    $detector->onBlock($block, $depth + 1);
    
                    $reset();
                    continue;
                }


                if ('{' === $result->token) {
                    $opened_curly++;
                }

                $missed .= $result->before.$result->token;
                continue;
            }

            if (!$result = $search->nextIgnoreQuotes('[', ']', '{', '}', ';')) {
                return ok();
            }

            if ('[' === $result->token) {
                $opened_square++;
                $missed .= $result->before.$result->token;
                continue;
            } else if (']' === $result->token) {
                $closed_square++;
                $missed .= $result->before.$result->token;
                continue;
            } else if ($opened_square !== $closed_square) {
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
                $opened_curly++;
                $block_found = true;
                continue;
            }

            $body .= $missed.$result->before.$result->token;
        }
    }
}
