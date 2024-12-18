<?php

namespace CatPaw\Web;

use Amp\Cancellation;
use Amp\Http\Server\Request;
use function CatPaw\Core\error;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use Throwable;

class Body {
    private null|Cancellation $cancellation = null;
    private int $sizeLimit                  = 1024 * 1024 * 1024 * 10; // 10GB
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
     * Project the properties of the 
     * resulting object/array onto `$target`.
     * @param  object       &$target
     * @return Result<None>
     */
    public function project(object &$target):Result {
        $attempt = BodyParser::parseAsObject(
            request: $this->request,
            sizeLimit: $this->sizeLimit,
            cancellation: $this->cancellation,
        );

        $response = $attempt->unwrap($error);

        if ($error) {
            return error($error);
        }

        foreach ($target as $key => &$value) {
            if (isset($response->$key)) {
                $value = $response->$key;
            }
        }

        return ok();
    }

    /**
     * @return Result<\stdClass>
     */
    public function parse():Result {
        return BodyParser::parseAsObject(
            request: $this->request,
            sizeLimit: $this->sizeLimit,
            cancellation: $this->cancellation,
        );
    }

    /**
     * @return Result<string>
     */
    public function text():Result {
        try {
            return ok($this->request->getBody()->buffer());
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Result<int>
     */
    public function int():Result {
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
     * @return Result<bool>
     */
    public function bool():Result {
        try {
            $body = $this->request->getBody()->buffer();
            return ok(filter_var($body, FILTER_VALIDATE_BOOLEAN));
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Result<float>
     */
    public function float():Result {
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
