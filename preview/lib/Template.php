<?php
namespace Preview;

use CatPaw\Superstyle\SuperstyleDocument;

final class Template {
    public static function htmx(SuperstyleDocument $document) {
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
}