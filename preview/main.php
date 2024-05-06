<?php
use function CatPaw\Core\asFileName;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use CatPaw\Superstyle\SuperstyleDocument;
use CatPaw\Web\Server;


function htmx(SuperstyleDocument $document) {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
            <script src="https://unpkg.com/htmx.org@1.9.12"></script>
            
        </head>
        <body>
            <style>{$document->style}</style>
            {$document->markup}
            <script>{$document->script}</script>
        </body>
        </html>
        HTML;
}

function main() {
    if (File::exists($tmp = asFileName(__DIR__, '../.tmp'))) {
        Directory::delete($tmp)->unwrap($error);
        if ($error) {
            return error($error);
        }
    }

    $server = Server::create(api: asFileName(__DIR__, './api'), www: asFileName(__DIR__, './www'))->unwrap($error);

    if ($error) {
        return error($error);
    }

    $server->start()->unwrap($error);

    if ($error) {
        return error($error);
    }

    return ok();
}