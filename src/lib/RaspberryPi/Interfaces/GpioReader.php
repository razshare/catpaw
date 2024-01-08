<?php

namespace CatPaw\RaspberryPi\Interfaces;

use CatPaw\Unsafe;

interface GpioReader {
    /**
     * Read data from the pin.
     * @return Unsafe<string>
     */
    public function read():Unsafe;

    /**
     * Close the pin reader.
     * @return void
     */
    public function close():void;
}