<?php

namespace CatPaw\Web;

use Amp\Cancellation;
use Amp\Http\Server\Request;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;
use Throwable;

class Body {
    private null|Cancellation $cancellation = null;
    private int $sizeLimit                  = 1024 * 1024 * 120;
    public function __construct(
        public readonly Request $request,
    ) {
    }

    public function withCancellation(Cancellation $cancellation):self {
        $this->cancellation = $cancellation;
        return $this;
    }

    public function withoutCancellation():self {
        $this->cancellation = null;
        return $this;
    }

    public function withSizeLimit(int $sizeLimit):self {
        $this->sizeLimit = $sizeLimit;
        return $this;
    }

    /**
     * @template T
     * @param  class-string<T> $className
     * @return Unsafe<T>
     */
    public function asObject(string $className = 'stdClass'):Unsafe {
        try {
            return BodyParser::parseAsObject(
                request: $this->request,
                sizeLimit: $this->sizeLimit,
                cancellation: $this->cancellation,
            );
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Unsafe<string>
     */
    public function asText():Unsafe {
        try {
            return ok($this->request->getBody()->buffer());
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Unsafe<int>
     */
    public function asInt():Unsafe {
        try {
            $body = $this->request->getBody()->buffer();
            if (is_numeric($body)) {
                return ok((int)$body);
            } else {
                return error('Body was expected to be numeric (int), but non numeric value has been provided instead:'.$body);
            }
        } catch(Throwable $error) {
            return error($error);
        }
    }


    /**
     * @return Unsafe<bool>
     */
    public function asBool():Unsafe {
        try {
            $body = $this->request->getBody()->buffer();
            return ok(filter_var($body, FILTER_VALIDATE_BOOLEAN));
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Unsafe<float>
     */
    public function asFloat():Unsafe {
        try {
            $body = $this->request->getBody()->buffer();
            if (is_numeric($body)) {
                return ok((float)$body);
            } else {
                return error('Body was expected to be numeric (float), but non numeric value has been provided instead:'.$body);
            }
        } catch(Throwable $error) {
            return error($error);
        }
    }
}
