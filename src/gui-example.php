<?php
use function CatPaw\Core\error;
use function CatPaw\Core\goffi;
use CatPaw\Gui\Contract;

use const CatPaw\Gui\LABEL_ALIGN_MIDDLE;
use const CatPaw\Gui\REF_CONTEXT;
use const CatPaw\Gui\REF_LABEL;
use const CatPaw\Gui\REF_RGBA;

function main() {
    $lib = goffi(Contract::class, './src/lib/Gui/lib/main.so')->try($error);
    if ($error) {
        return error($error);
    }

    $window = $lib->window();
    $theme  = $lib->theme();
    while (true) {
        $event = $lib->event($window);
        $t     = $event->r1;
        $event = $event->r0;

        if ($event < 0) {
            continue;
        }

        if (1 === $t) {
            $lib->reset();

            $context = $lib->context($event);
            $title   = $lib->h1($theme, "Hello from CatPaw");
            $maroon  = $lib->rgba(127, 0, 0, 255);

            $lib->labelSetColor($title, $maroon);
            $lib->labelSetAlignment($title, LABEL_ALIGN_MIDDLE);
            $lib->labelLayout($title, $context);
            $lib->draw($event);

            $lib->remove($context, REF_CONTEXT);
            $lib->remove($title, REF_LABEL);
            $lib->remove($maroon, REF_RGBA);
        } else if (2 === $t) {
            die();
        }
    }
}
