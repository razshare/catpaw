<?php
use CatPaw\Web\Interfaces\RenderInterface;
use Preview\Components\LayoutComponent;
return static function(
    RenderInterface $render,
    LayoutComponent $layout,
    string $name = 'world',
) {
    $render->start() ?>
    <?php $layout->mount('My Document', static function() use ($name) { ?>
        <h3>Hello, <?=$name?>.</h3>
    <?php }) ?>
<?php } ?>