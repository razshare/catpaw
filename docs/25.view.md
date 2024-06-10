# View

You can render twig files and serve them to the client using _view()_.

## Usage

Create a latte file, for example under _src/api/view.latte_

```twig
hello world!
```



Now create your route handler and respond with `view()`

```php
<?php
use function CatPaw\Web\view;
return fn () => view();
```

That's all it takes, your endpoint will respond with the twig view.

## Properties

You can set specific properties on your twig view with `view::withProperty()`

```php
<?php
use function CatPaw\Web\view;
return fn () => view()
                ->withProperty('title', 'My Document')
                ->withProperty('name', 'world')
                ->withProperty('fileName', 'view.twig');
```

Or you can set all the properties with `view::withProperties()`

```php
<?php
use function CatPaw\Web\view;
return fn () => view()->properties([
    'title'    => 'My Document',
    'name'     => 'world',
    'fileName' => 'view.twig',
]);
```

Then you can access those properties in your twig view, like you normally would.

```latte
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{$title}</title>
</head>
<body>
    hello {$name}, this is {$fileName} speaking!
</body>
</html>
```

## Loading components

You can load components from directories with `loadComponentsFromDirectory()`

```php
<?php
use function CatPaw\Core\anyError;
use function CatPaw\Web\loadComponentsFromDirectory;
use CatPaw\Web\Interfaces\ServerInterface;

function main(ServerInterface $server) {
    return anyError(function() use($server) {
        loadComponentsFromDirectory('src/components')->try();
        return $server->withApiLocation('src/api')->start();
    });
}
```

This will load all files in the `components` directory.
You will then be able to reference these files in your twig views by using their filename relative to the directory which you provided.

For example if you have a component located in `components/buttons/red-button.latte`, you can reference it by `buttons/red-button.latte`, like so

```latte
{extends 'button.latte'}

{block content}click me!{/block}
```