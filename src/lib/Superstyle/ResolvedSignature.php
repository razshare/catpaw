<?php
namespace CatPaw\Superstyle;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\StringStack;
use CatPaw\Core\Unsafe;

readonly class ResolvedSignature {
    private function __construct(
        public string $attributes,
        public string $tagName,
    ) {
    }

    /**
     * 
     * @param  string                    $signature
     * @return Unsafe<ResolvedSignature>
     */
    public static function resolve(string $signature): Unsafe {
        $cleanSignature   = '';
        $stack            = StringStack::of($signature)->expect('[', ']');
        $readingAttribute = false;
        /** @var array<string> $attributes */
        $attributes = [];
        for ($stack->rewind(); $stack->valid(); $stack->next()) {
            /**
             * @var string       $previous
             * @var false|string $current
             */
            [$previous, $current] = $stack->current();

            if (false === $current) {
                $cleanSignature .= $previous;
                continue;
            }

            if ($readingAttribute && ']' === $current) {
                $trimmedPrevious = trim($previous);
                if (str_starts_with($trimmedPrevious, 'class ') || str_starts_with($trimmedPrevious, 'class=') || 'class' === $trimmedPrevious) {
                    $attributes['class'] = $previous;
                } else if (str_starts_with($trimmedPrevious, 'id ') || str_starts_with($trimmedPrevious, 'id=') || 'id' === $trimmedPrevious) {
                    $attributes['id'] = $previous;
                } else {
                    $attributes[] = $previous;
                }
                
                continue;
            }

            if ('[' === $current) {
                $cleanSignature .= $previous;
                $readingAttribute = true;
            }
        }

        $classNames = [];

        while (preg_match('/\.([A-z0-9-_]+)/', $cleanSignature, $matches)) {
            $cleanSignature = preg_replace('/\.[A-z0-9-_]+/', '', $cleanSignature);
            $classNames[]   = $matches[1];
        }

        $ids = [];

        while (preg_match('/\#([A-z0-9-_]+)/', $cleanSignature, $matches)) {
            $cleanSignature = preg_replace('/\#[A-z0-9-_]+/', '', $cleanSignature);
            $ids[]          = $matches[1];
        }

        if (($idCount = count($ids)) > 2) {
            return error("An element may have only on identifier, $idCount received in signature `$signature`.");
        }

        if ($classNames) {
            $stringifiedClassNames = join(' ', $classNames);
            if (!isset($attributes['class'])) {
                $attributes['class'] = "class=\"$stringifiedClassNames\"";
            } else {
                $delimiterAndChunkOfClass = preg_replace('/^\s*class\s*=\s*/', '', $attributes['class']);
                $delimiter                = substr($delimiterAndChunkOfClass, 0, 1);
                $chunkOfClass             = substr($delimiterAndChunkOfClass, 1);

                $attributes['class'] = "class=$delimiter$stringifiedClassNames $chunkOfClass";
            }
        }

        if ($idCount > 0) {
            if (isset($attributes['id'])) {
                return error("Tag identifiers cannot appear both as `#id` syntax and `[id=\"id\"]`, you must use one or the other, received both in signature `$signature`.");
            }
            $id               = $ids[0];
            $attributes['id'] = "id=\"$id\"";
        }

        if (!$attributes) {
            return ok(new ResolvedSignature(attributes: '', tagName: $cleanSignature));
        }

        return ok(new ResolvedSignature(attributes: ' '.join(' ', $attributes), tagName: $cleanSignature));
    }
}