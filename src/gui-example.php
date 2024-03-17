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

    $logo_file_name = asFileName(__DIR__, "../php-logo.png");

    $lib->application();

    $window = $lib->window();
    $lib->window_set_title($window, "hello world");
    $lib->window_set_minimum_size($window, 360, 520);

    $status_bar = $lib->status_bar($window);
    $lib->window_set_status_bar($window, $status_bar);

    $scene = $lib->scene();
    $view  = $lib->view();

    $lib->scene_match_window($scene, $window);


    // ==== START ADDING STUFF ====

    // TEXT
    $text = $lib->text($scene, "hello world");
    $lib->text_set_position($text, 0, 0);

    // LOGO
    $logo = $lib->image_from_file_name($logo_file_name, "png");
    $item = $lib->image_add_to_scene($logo, $scene);
    $lib->pixmap_item_set_position($item, 100, 100);

    // BUTTON
    $button = $lib->button("This is a button that doesn't work... yet.");
    $proxy  = $lib->button_add_to_scene($button, $scene);
    $lib->proxy_widget_set_position($proxy, 0, 50);


    // ==== END ADDING STUFF ====

    $lib->view_set_scene($view, $scene);
    $lib->view_show($view);

    $lib->status_bar_show_message($status_bar, "this is a status bar");

    $lib->window_set_central_view($window, $view);

    $lib->application_set_style("fusion");
    $lib->window_show($window);
    $lib->application_execute();

    echo "Started\n";
    while (true) {
        echo "looping...\n";
        delay(10);
    }
}
