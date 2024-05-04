<?php
namespace CatPaw\Cui\Services;

use function Amp\delay;
use function CatPaw\Core\asFileName;

use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;

use CatPaw\Core\None;

use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;
use CatPaw\Cui\Contracts\CuiContract;
use CatPaw\Go\Services\GoLoaderService;

#[Service]
class CuiService {
    private false|CuiContract $lib = false;
    private float $delay           = 0.1;

    public function __construct(
        private GoLoaderService $loader,
    ) {
    }

    /**
     * Load the library using cached binaries (if possible).
     * @param  bool         $clear
     * @return Unsafe<None>
     */
    public function load(bool $clear = false):Unsafe {
        $directoryName = asFileName(__DIR__, '../../Go/lib');
        $this->lib     = $this->loader->load(CuiContract::class, $directoryName, $clear)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $this->lib->NewGui();
        $this->lib->StartGui();
        return ok();
    }

    /**
     *
     * @param  float        $delay
     * @return Unsafe<None>
     */
    public function setDelay(float $delay):Unsafe {
        if ($delay < 0.001) {
            return error("Delay cannot be lower than `0.001`.");
        }
        $this->delay = $delay;
        return ok();
    }

    /**
     *
     * @param  callable(CuiContract):void $function
     * @return Unsafe<None>
     */
    public function loop(callable $function):Unsafe {
        if (!$this->lib) {
            return error("You must first run `loadLibrary()` the CuiService.");
        }
        $lastUpdate = time();
        $function($this->lib);
        $previous = 0;
        // @phpstan-ignore-next-line
        while (true) {
            $now         = time();
            $delta       = $now - $lastUpdate;
            $forceUpdate = $delta > 1;
            $next        = $this->lib->Update($forceUpdate?1:0);
            if ($next > $previous) {
                $function($this->lib);
                $previous = $next;
            }

            delay($this->delay);
        }
    }
}
