<?php
use function CatPaw\Core\asFileName;

use CatPaw\Core\Directory;

use function CatPaw\Core\error;
use CatPaw\Core\File;

use function CatPaw\Core\ok;
use CatPaw\Web\Server;

function main() {
    if (File::exists($tmp = asFileName(__DIR__, '../.tmp'))) {
        Directory::delete($tmp)->unwrap($error);
    }
    
    if ($error) {
        return error($error);
    }

    $server = Server::create(api: asFileName(__DIR__, './api'))->unwrap($error);

    if ($error) {
        return error($error);
    }

    $server->start()->unwrap($error);

    if ($error) {
        return error($error);
    }

    return ok();
}