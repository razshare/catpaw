<?php
namespace CatPaw\Ast;

use CatPaw\Ast\Interfaces\CStyleDetector;
use function CatPaw\Core\anyError;
use CatPaw\Core\File;
use CatPaw\Core\Unsafe;

class Search {
    /**
     *
     * @param  string         $fileName
     * @return Unsafe<Search>
     */
    public static function fromFile(string $fileName):Unsafe {
        return anyError(function() use ($fileName) {
            $file = File::open($fileName)->try($error)
            or yield $error;

            $source = $file->readAll()->await()->try($error)
            or yield $error;

            return self::fromSource($source);
        });
    }
    public static function fromSource(string $source):self {
        return new self(source: $source);
    }
    private string $previousSource;
    private function __construct(
        public string $source,
    ) {
        $this->previousSource = $this->source;
    }

    public function resetToPreviousSource() {
        $this->source = $this->previousSource;
    }

    /**
     * Search the next occurrence of `$tokens`.
     * @param  string             ...$tokens
     * @return false|SearchResult
     */
    public function next(string ...$tokens):false|SearchResult {
        $length = strlen($this->source);
        $before = '';
        for ($i = 0; $i < $length; $i++) {
            $before .= $this->source[$i];
            foreach ($tokens as $token) {
                if (str_ends_with($before, $token)) {
                    $this->previousSource = $this->source;
                    $this->source         = substr($this->source, $i + 1);
                    return new SearchResult(
                        token: $token,
                        before: substr($before, 0, -strlen($token)),
                    );
                }
            }
        }
        return false;
    }

    /**
     * Parse source code as C style.
     * @param  bool|Block                          $parent
     * @param  callable(Block $block):void         $on_block
     * @param  false|(callable(string $rule):void) $on_global_rule
     * @return void
     */
    public function cStyle(
        CStyleDetector $detector,
        false|Block $parent = false,
    ):void {
        $uncommented = '';
        $search      = Search::fromSource(preg_replace('/^\s*\/\/.*/', '', $this->source));

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
                    return;
                }
            }
        }

        $search = Search::fromSource($uncommented);

        $name              = '';
        $opened_braces     = 0;
        $closed_braces     = 0;
        $double_quotes     = 0;
        $single_quotes     = 0;
        $escaped_character = false;
        $body              = '';
        $rules             = [];
        $missed            = '';

        while (true) {
            if (!$result = $search->next('"', '\'', '\\', '{', '}', ';')) {
                return;
            }

            if ($opened_braces < 2) {
                if ('"' === $result->token && 0 === $single_quotes % 2) {
                    $missed .= $result->before.$result->token;
                    if (!$escaped_character) {
                        $double_quotes++;
                    } else {
                        $escaped_character = false;
                    }
                    continue;
                }

                if (0 !== $double_quotes % 2) {
                    if ('\\' === $result->token) {
                        $escaped_character = true;
                    } else {
                        $escaped_character = false;
                    }
                    $missed .= $result->before.$result->token;
                    continue;
                }

                if ('\'' === $result->token) {
                    $missed .= $result->before.$result->token;
                    if (!$escaped_character) {
                        $single_quotes++;
                    } else {
                        $escaped_character = false;
                    }
                    continue;
                }

                if (0 !== $single_quotes % 2) {
                    if ('\\' === $result->token) {
                        $escaped_character = true;
                    } else {
                        $escaped_character = false;
                    }
                    $missed .= $result->before.$result->token;
                    continue;
                }

                $escaped_character = false;

                if (';' === $result->token) {
                    if ('' !== $missed) {
                        $missed .= $result->before;
                        if ('' === $name) {
                            $detector->on_global(trim($missed));
                        } else {
                            $rules[] = trim($missed);
                        }
                        $missed = '';
                    } else {
                        if ('' === $name) {
                            $detector->on_global(trim($result->before));
                        } else {
                            $rules[] = trim($result->before);
                        }
                    }
                    continue;
                }
            }

            if ('{' === $result->token) {
                if (0 === $opened_braces) {
                    $name = trim($result->before);
                } else {
                    $body .= $missed.$result->before.$result->token;
                    $missed = '';
                }
                $opened_braces++;
                continue;
            }

            if (0 === $opened_braces) {
                return;
            }

            if ('}' === $result->token) {
                $closed_braces++;
                if ($closed_braces === $opened_braces) {
                    $body .= $missed.$result->before;
                    $missed       = '';
                    $this->source = $search->source;
                    $block        = new Block(
                        body: trim($body),
                        name: $name,
                        parent: $parent,
                        rules: $rules,
                    );

                    $detector->on_block($block);

                    // $this->blocks($function, $block);


                    if ($block->body) {
                        $search = Search::fromSource($block->body);
                        $search->cStyle($detector, $block);
                    }

                    if ($this->source) {
                        $search = Search::fromSource($this->source);
                        $search->cStyle($detector, $parent);
                    }

                    return;
                } else {
                    $body .= $missed.$result->before.$result->token;
                    $missed = '';
                }
                continue;
            }

            $body .= $missed.$result->before.$result
            ->token;
        }
    }
}
