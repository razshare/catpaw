<?php
namespace CatPaw\Screen\Implementations\Screen;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Attributes\Entry;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Go\Interfaces\GoInterface;
use CatPaw\Screen\Interfaces\CaptureScreen;
use CatPaw\Screen\Interfaces\ScreenInterface;

#[Provider]
class SimpleScreen implements ScreenInterface {
    /** @var CaptureScreen */
    private $library;
    /**
     * @param  GoInterface  $go
     * @return Unsafe<None>
     */
    #[Entry] public function start(GoInterface $go):Unsafe {
        return anyError(function() use ($go) {
            $this->library = $go->load(CaptureScreen::class, asFileName(__DIR__, '../../../Go/Resources/CaptureScreen/main.so'))->try();
            return ok();
        });
    }

    public function capture(string $fileName): void {
        $this->library->CaptureScreen($fileName);
    }
}