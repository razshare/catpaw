<?php
namespace CatPaw\RaspberryPi\Interfaces;

interface GpioInterface {
    /**
     * Create a pin reader.
     * @param  string              $pin can be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`.
     * @return GpioReaderInterface
     */
    public function createReader(string $pin):GpioReaderInterface;
    

    /**
     * Create a pin writer.
     * @param  string              $pin can be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`.
     * @return GpioWriterInterface
     */
    public function createWriter(string $pin):GpioWriterInterface;
}