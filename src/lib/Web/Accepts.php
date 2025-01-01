<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Stringable;

class Accepts implements Stringable {
    /**
     *
     * @param  array<string> $accepts
     * @return void
     */
    private function __construct(private array $accepts) {
    }

    public function __toString():string {
        return join(',', $this->accepts);
    }

    public function match(string $pattern):bool {
        foreach ($this->accepts as $value) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    public function json():bool {
        foreach ($this->accepts as $value) {
            if (APPLICATION_JSON === $value) {
                return true;
            }
        }
        return false;
    }

    public function xml():bool {
        foreach ($this->accepts as $value) {
            if (APPLICATION_XML === $value) {
                return true;
            }
        }
        return false;
    }

    public function plain():bool {
        foreach ($this->accepts as $value) {
            if (TEXT_PLAIN === $value) {
                return true;
            }
        }
        return false;
    }

    public function html():bool {
        foreach ($this->accepts as $value) {
            if (TEXT_HTML === $value) {
                return true;
            }
        }
        return false;
    }

    public static function fromRequest(Request $request):self {
        $received = $request->getHeader('Accept') ?? '*/*';

        return new self(explode(',', $received));
    }
}
