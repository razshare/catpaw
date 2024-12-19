<?php
namespace Preview\Components;

use CatPaw\Core\Attributes\Provider;
#[Provider] class LayoutComponent {
    public function mount(string $title, callable $slot) { ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?=$title?></title>
            </head>
            <body>
                <?=$slot()?>
            </body>
        </html>
    <?php }
    }
?>