<?php

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Directory;
use CatPaw\Core\File;
use CatPaw\Superstyle\SuperstyleDocument;
use CatPaw\Web\Server;
use CatPaw\Web\Services\HandlebarsService;

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

function main(HandlebarsService $handlebars) {
    return anyError(function() use ($handlebars) {
        $handlebars->withTemporaryDirectory(asFileName(__DIR__, './temp'));

        if (File::exists($tmp = asFileName(__DIR__, './temp'))) {
            Directory::delete($tmp)->try();
        }
        
        Server::get()
            ->withApiLocation(asFileName(__DIR__, './api'))
            ->start()
            ->try();
    });
}