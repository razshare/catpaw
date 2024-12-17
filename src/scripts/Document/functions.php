<?php

/**
 * Expose the current document.
 * @param  string        $path
 * @param  array<string> $methods
 * @return void
 */
function expose(string $path, $methods = ['GET', 'POST', 'PUT', 'DELETE']):void {
    $GLOBALS['CURRENT_DOCUMENT_EXPOSE'] = [
        'path'    => $path,
        'methods' => $methods,
    ];
}