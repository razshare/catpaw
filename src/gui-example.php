<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Core\error;

use function CatPaw\Core\goffi;
use CatPaw\Gui\Contract;

use const CatPaw\Gui\LABEL_ALIGN_MIDDLE;
use const CatPaw\Gui\REF_CONTEXT;
use const CatPaw\Gui\REF_LABEL;
use const CatPaw\Gui\REF_RGBA;

function main() {
    $lib = goffi(Contract::class, asFileName(__DIR__, './lib/Gui/lib/main.so')->withPhar())->try($error);
    if ($error) {
        return error($error);
    }

    $black  = $lib->rgba(0, 0, 0, 255);
    $window = $lib->window();
    $theme  = $lib->theme();
    $file   = $lib->openFile(asFileName(__DIR__, '../php-logo.png'));
    $logo   = $lib->decodeImage($file);

    $operations = $lib->operations();

    while (true) {
        $event = $lib->event($window);
        $t     = $event->r1;
        $event = $event->r0;

        if ($event < 0) {
            continue;
        }

        if (1 === $t) {
            $lib->reset($operations);
            $context = $lib->context($operations, $event);

            $lib->addImage($operations, $logo);


            $title  = $lib->h1($theme, "Hello from CatPaw");
            $maroon = $lib->rgba(127, 0, 0, 255);

            $line = $lib->pathStart($operations, 220, 150);
            $lib->lineTo($line, 30, 70);
            $lib->arcTo($line, 100, 100, 200, 200, M_PI * 2);
            $lib->lineTo($line, 70, 30);
            $lib->pathEnd($operations, $line, 3, $black);

            $lib->labelSetColor($title, $maroon);
            $lib->labelSetAlignment($title, LABEL_ALIGN_MIDDLE);

            $lib->labelLayout($title, $context);


            $lib->draw($operations, $event);

            $lib->destroy($context, REF_CONTEXT);
            $lib->destroy($title, REF_LABEL);
            $lib->destroy($maroon, REF_RGBA);
        } else if (2 === $t) {
            die();
        }
    }
}
