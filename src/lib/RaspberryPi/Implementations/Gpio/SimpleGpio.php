<?php

namespace CatPaw\RaspberryPi\Implementations\Gpio;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\RaspberryPi\Interfaces\GpioInterface;
use CatPaw\RaspberryPi\Interfaces\GpioReaderInterface;
use CatPaw\RaspberryPi\Interfaces\GpioWriterInterface;

#[Provider]
class SimpleGpio implements GpioInterface {
    private const READ        = 0;
    private const WRITE       = 1;
    private const HEADER7     = 4;
    private const HEADER11    = 17;
    private const HEADER12    = 18;
    private const HEADER13rv1 = 21;
    private const HEADER13rv2 = 27;
    private const HEADER15    = 22;
    private const HEADER16    = 23;
    private const HEADER18    = 24;
    private const HEADER22    = 25;

    /**
     * Export the pin and return its file handler.
     * @param  string       $pin       can be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`.
     * @param  int          $direction direction of the pin, `0` means `read` and `1` means `write`.
     * @return Result<File>
     */
    private function export(string $pin, int $direction):Result {
        $originalPin = $pin;
        $pin         = match ($pin) {
            '7'     => self::HEADER7,
            '11'    => self::HEADER11,
            '12'    => self::HEADER12,
            '13rv1' => self::HEADER13rv1,
            '13rv2',
            '13'    => self::HEADER13rv2,
            '15'    => self::HEADER15,
            '16'    => self::HEADER16,
            '18'    => self::HEADER18,
            '22'    => self::HEADER22,
            default => -1,
        };

        if (-1 === $pin) {
            return error("Pin name must be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`. Received '$originalPin'.");
        }

        $exportFile = File::open('/sys/class/gpio/export', 'a')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $exportFile->write((string)$pin);
        $exportFile->close();

        if (!File::exists("/sys/class/gpio/gpio$pin/direction")) {
            Directory::create("/sys/class/gpio/gpio$pin")->unwrap($error);
            if ($error) {
                return error($error);
            }
            $directionFile = File::open("/sys/class/gpio/gpio$pin/direction", 'w')->unwrap($error);
            if ($error) {
                return error($error);
            }
            $directionFile->write('')->unwrap($error);
            if ($error) {
                return error($error);
            }
            $directionFile->close();
        }

        $directionFile = File::open("/sys/class/gpio/gpio$pin/direction", 'a')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $directionFile->write($direction > 0 ? 'out' : 'in');
        $directionFile->close();


        if (!File::exists("/sys/class/gpio/gpio$pin/value")) {
            Directory::create("/sys/class/gpio/gpio$pin")->unwrap($error);
            if ($error) {
                return error($error);
            }
            $valueFile = File::open("/sys/class/gpio/gpio$pin/value", 'w')->unwrap($error);
            if ($error) {
                return error($error);
            }
            $valueFile->write('')->unwrap($error);
            if ($error) {
                return error($error);
            }
            $valueFile->close();
        }

        return File::open("/sys/class/gpio/gpio$pin/value", $direction > 0 ? 'a' : 'r');
    }

    /**
     * Create a pin reader.
     * @param  string              $pin can be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`.
     * @return GpioReaderInterface
     */
    public function createReader(string $pin):GpioReaderInterface {
        $export = fn () => $this->export($pin, self::READ);
        return new class($export) implements GpioReaderInterface {
            private File|false $file = false;
            /**
             *
             * @param  callable():Result<File> $export
             * @return void
             */
            public function __construct(private $export) {
            }

            public function read():Result {
                if (!$this->file) {
                    $export = $this->export;
                    $file   = $export()->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $this->file = $file;
                }
                return $this->file->read();
            }

            public function close():void {
                $this->file->close();
            }
        };
    }

    /**
     * Create a pin writer.
     * @param  string              $pin can be one of the following: `7`,`11`,`12`,`13rv1`,`13`,`13rv2`,`15`,`16`,`18`,`22`.
     * @return GpioWriterInterface
     */
    public function createWriter(string $pin):GpioWriterInterface {
        $export = fn () => $this->export($pin, self::WRITE);
        return new class($export) implements GpioWriterInterface {
            private File|false $file = false;
            /**
             *
             * @param  callable():Result<File> $export
             * @return void
             */
            public function __construct(private $export) {
            }

            /**
             *
             * @param  string       $data
             * @return Result<None>
             */
            public function write(string $data):Result {
                if (!$this->file) {
                    $export = $this->export;
                    $file   = $export()->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $this->file = $file;
                }
                return $this->file->write($data);
            }

            public function close():void {
                $this->file->close();
            }
        };
    }
}
