<?php

namespace CatPaw\RaspberryPi\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface GpioWriterInterface {
    /**
     * Write data to the pin.
     * @param  string       $data
     * @return Result<None>
     */
    public function write(string $data):Result;

    /**
     * Close the pin writer.
     * @return void
     */
    public function close():void;
}
