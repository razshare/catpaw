<?php

namespace CatPaw\RaspberryPi\Interfaces;

use CatPaw\Core\Result;

interface GpioReaderInterface {
    /**
     * Read data from the pin.
     * @return Result<string>
     */
    public function read():Result;

    /**
     * Close the pin reader.
     * @return void
     */
    public function close():void;
}