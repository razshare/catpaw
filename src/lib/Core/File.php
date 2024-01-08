<?php
namespace CatPaw;

use function Amp\async;
use Amp\ByteStream\ReadableStream;

use function Amp\File\deleteFile;
use function Amp\File\exists;
use Amp\File\File as AmpFile;
use function Amp\File\getModificationTime;
use function Amp\File\getSize;
use function Amp\File\getStatus;
use function Amp\File\openFile;
use Amp\Future;
use Throwable;

readonly class File {
    /**
     * @param  string      $fileName
     * @return Unsafe<int>
     */
    public static function getSize(string $fileName):Unsafe {
        $size = filesize($fileName);
        if (false === $size) {
            return error("Could not retrieve size of file $fileName.");
        }

        return ok($size);
    }

    /**
     * Copy a file.
     * @param  string               $from
     * @param  string               $to
     * @return Future<Unsafe<void>>
     */
    public static function copy(string $from, string $to):Future {
        return async(static function() use ($from, $to) {
            $source = File::open($from);
            if ($source->error) {
                return error($source->error);
            }

            $toDirectory = dirname($to);

            if (!File::exists($toDirectory)) {
                if ($error = Directory::create($toDirectory)->error) {
                    return error($error);
                }
            }

            $destination = File::open($to, 'x');

            if ($destination->error) {
                return error($destination->error);
            }

            $stream = $source->value->getAmpFile();

            return $destination->value->writeStream($stream)->await();
        });
    }


    /**
     * @return Unsafe<array>
     */
    public static function getStatus(string $fileName):Unsafe {
        $status = getStatus($fileName);
        if (null === $status) {
            return error("Could not get status of file $fileName because it doesn't exist.");
        }
        return ok($status);
    }

    /**
     * @return Unsafe<int>
     */
    public static function getModificationTime(string $fileName):Unsafe {
        try {
            $mtime = getModificationTime($fileName);
            return ok($mtime);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    public static function exists(string $fileName):bool {
        return exists($fileName);
    }

    public static function delete(string $fileName):Unsafe {
        try {
            deleteFile($fileName);
            return ok();
        } catch (Throwable $e) {
            return error($e);
        }
    }

    /**
     * @return Unsafe<File>
     */
    public static function open(string $fileName, string $mode = 'r'):Unsafe {
        try {
            $file = openFile($fileName, $mode);
        } catch(Throwable $e) {
            return error($e);
        }
        return ok(new self($file, $mode, $fileName));
    }

    private function __construct(
        private AmpFile $ampFile,
        private string $mode,
        private string $fileName,
    ) {
    }
    
    /**
     * @return Future<Unsafe<void>>
     */
    public function write(string $content, int $chunkSize = 8192):Future {
        $ampFile = $this->ampFile;
        return async(static function() use ($ampFile, $content, $chunkSize) {
            try {
                $wroteSoFar = 0;
                $length     = strlen($content);
                while (true) {
                    $step  = $wroteSoFar + $chunkSize;
                    $chunk = substr($content, $wroteSoFar, $step);
                    async(static fn () => $ampFile->write($chunk))->await();
                    $wroteSoFar = $wroteSoFar + $step;
                    if ($wroteSoFar >= $length) {
                        return ok();
                    }
                }
            } catch(Throwable $e) {
                return error($e);
            }
        });
    }

    /**
     * @return Future<Unsafe<void>>
     */
    public function writeStream(ReadableStream $readableStream, int $chunkSize = 8192):Future {
        $ampFile = $this->ampFile;
        return async(function() use ($ampFile, $readableStream, $chunkSize) {
            try {
                while (true) {
                    $chunk = async(static fn () => $readableStream->read(null, $chunkSize))->await();
                    if (null === $chunk) {
                        return ok();
                    }
                    async(static fn () => $ampFile->write($chunk))->await();
                }
            } catch(Throwable $e) {
                return error($e);
            }
        });
    }

    /**
     * @param int $position
     * @return int
     */
    public function seek(int $position): int {
        return $this->ampFile->seek($position);
    }

    /**
     * @return Future<Unsafe<string>>
     */
    public function read(int $length = 8192, int $chunkSize = 8192):Future {
        $ampFile = $this->ampFile;
        return async(static function() use ($ampFile, $length, $chunkSize) {
            try {
                $readSoFar = 0;
                $buffer    = '';
                while (true) {
                    if ($length < $readSoFar + $chunkSize) {
                        $step = $length - $readSoFar;
                    } else {
                        $step = $chunkSize;
                    }
                    $chunk     = async(static fn () => $ampFile->read(null, $step))->await();
                    $readSoFar = $readSoFar + $step;
                    if (null === $chunk) {
                        return ok($buffer);
                    }
                    $buffer = $buffer.$chunk;
                    if ($readSoFar >= $length) {
                        return ok($buffer);
                    }
                }
            } catch(Throwable $e) {
                return error($e);
            }
        });
    }
    
    /**
     * @return Future<Unsafe<string>>
     */
    public function readAll(int $chunkSize = 8192):Future {
        $fileName = $this->fileName;
        return async(function() use ($fileName, $chunkSize) {
            $fileSize = async(static fn () => getSize($fileName))->await();
            return $this->read($fileSize, $chunkSize)->await();
        });
    }

    /**
     * @return AmpFile
     */
    public function getAmpFile():AmpFile {
        return $this->ampFile;
    }


    public function close(): void {
        if ($this->ampFile->isClosed()) {
            return;
        }
        $this->ampFile->close();
    }
}
