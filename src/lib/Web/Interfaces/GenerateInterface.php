<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface GenerateInterface {
    /**
     * Start the server and generate a static website directory.
     * @param  string       $interface       Interface to bind to.\
     *                                       For example 0.0.0.0:80.\
     *                                       The default interface is 127.0.0.1:8080.
     * @param  string       $outputDirectory The output directory where the documents will be written.
     * @return Result<None>
     */
    public function generate(string $interface = '127.0.0.1:8080', string $outputDirectory = 'generated'):Result;
}