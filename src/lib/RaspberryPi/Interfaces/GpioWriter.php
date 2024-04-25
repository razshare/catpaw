<?php

namespace CatPaw\RaspberryPi\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface GpioWriter {
    /**
     * Write data to the pin.
     * @param  string       $data
     * @return Unsafe<None>
     */
    public function write(string $data):Unsafe;

    /**
     * Close the pin writer.
     * @return void
     */
    public function close():void;
}
