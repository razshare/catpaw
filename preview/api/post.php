<?php

use function CatPaw\Core\asFileName;
use CatPaw\Web\Body;

use function CatPaw\Web\failure;
use CatPaw\Web\FormFile;

use const CatPaw\Web\TEXT_HTML;
use function CatPaw\Web\view;

use Psr\Log\LoggerInterface;

return function(Body $body, LoggerInterface $logger) {
    $properties = $body->asObject()->unwrap($error);
    if ($error) {
        $logger->error($error);
        return failure($error)->as(TEXT_HTML);
    }
        
    if ($properties->file1 instanceof FormFile) {
        $properties->file1->saveAs(asFileName(__DIR__, $properties->file1->fileName))->unwrap($error);
        if ($error) {
            return failure($error)->as(TEXT_HTML);
        }
    }

    return view()->withProperties($properties);
};