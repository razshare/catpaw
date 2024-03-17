<?php

use function Amp\delay;
use function CatPaw\Core\asFileName;
use function CatPaw\Core\error;

use function CatPaw\Core\goffi;
use CatPaw\Gui\Contract;


function main() {
    $lib = goffi(Contract::class, asFileName(__DIR__, './lib/Gui/lib/main.so')->withPhar())->try($error);
    if ($error) {
        return error($error);
    }

    $lib->application();

    $window = $lib->window();
    $lib->window_set_title($window, "hello world");
    $lib->window_set_minimum_size($window, 360, 520);

    $status_bar = $lib->status_bar($window);
    $lib->window_set_status_bar($window, $status_bar);

    $scene = $lib->scene();
    $view  = $lib->view();

    $text = $lib->text($scene, "hello world");
    $lib->text_set_position($text, 20, 20);

    $lib->view_set_scene($view, $scene);
    $lib->view_show($view);

    $lib->status_bar_show_message($status_bar, "this is a status bar");

    $lib->window_set_central_view($window, $view);

    $lib->application_set_style("fusion");
    $lib->window_show($window);
    $lib->application_execute();

    echo "Started\n";
    while (true) {
        delay(10);
    }
}
