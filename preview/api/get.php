<?php
use CatPaw\Web\Interfaces\RenderInterface;
?>
<?php return static function(RenderInterface $render, string $name = '') { ?>
    <?php $render->start() ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
        </head>
        <body>
            <h3>Hello <?=$name?></h3>
        </body>
    </html>
<?php } ?>