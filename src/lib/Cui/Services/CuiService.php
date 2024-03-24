<?php
namespace CatPaw\Cui\Services;

use function Amp\delay;
use function CatPaw\Core\asFileName;

use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;

use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;
use CatPaw\Cui\Contracts\CuiContract;
use CatPaw\Go\Services\LoaderService;

#[Service]
class CuiService {
    /** @var CuiContract */
    private mixed $lib;
    private float $delay = 0.1;

    public function __construct(
        private LoaderService $loader,
    ) {
    }

    /**
     * Load the library using cached binaries (if possible).
     * @param  bool         $rebuild
     * @return Unsafe<void>
     */
    public function load(bool $clear = false):Unsafe {
        $directoryName = asFileName(__DIR__, '../../Go/lib');
        $this->lib     = $this->loader->load(CuiContract::class, $directoryName, $clear)->try($error);
        if ($error) {
            return error($error);
        }

        $this->lib->NewGui();
        $this->lib->StartGui();
        return ok();
    }

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
     * @return Unsafe<void>
     */
    public function loop(callable $function):Unsafe {
        if (!$this->lib) {
            return error("You must first run `loadLibrary()` the CuiService.");
        }
        $lastUpdate = time();
        $function($this->lib);
        $previous = 0;
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
