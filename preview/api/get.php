<?php return static fn () => \CatPaw\Web\render(function(string $name = 'world') { ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome</title>
    </head>
    <body>
        <span>Hello <?=$name?></span>
    </body>
    </html>
<?php }); ?>