<?php
namespace CatPaw\Core;

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
use Dotenv\Dotenv;
use Error;
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
     *
     * @param  string       $fileNameA
     * @param  string       $fileNameB
     * @param  bool         $binary
     * @return Unsafe<bool>
     */
    public static function checksum(string $fileNameA, string $fileNameB, bool $binary = false):Unsafe {
        $fileA = File::open($fileNameA)->try($error);
        if ($error) {
            return error($error);
        }

        $fileB = File::open($fileNameB)->try($error);
        if ($error) {
            return error($error);
        }


        $contentA = $fileA->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }

        $contentB = $fileB->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }

        $md5A = md5($contentA, $binary);
        $md5B = md5($contentB, $binary);

        return ok($md5A === $md5B);
    }

    /**
     * Copy a file.
     * @param  string               $from
     * @param  string               $to
     * @return Future<Unsafe<void>>
     */
    public static function copy(string $from, string $to):Future {
        return async(static function() use ($from, $to) {
            $source = File::open($from)->try($error);
            if ($error) {
                return error($error);
            }

            $toDirectory = dirname($to);

            if (!File::exists($toDirectory)) {
                Directory::create($toDirectory)->try($error);
                if ($error) {
                    return error($error);
                }
            }

            $dirname = dirname($to);

            if (false === $dirname) {
                Directory::create($dirname)->try($error);
                if ($error) {
                    return $error;
                }
            }

            if (!File::exists($dirname)) {
            }

            $destination = File::open($to, 'x')->try($error);

            if ($error) {
                return error($error);
            }

            $stream = $source->getAmpFile();

            return $destination->writeStream($stream)->await();
        });
    }


    /**
     * @param  string        $fileName
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
     * @param  string      $fileName
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

    /**
     * @param  string $fileName
     * @return bool
     */
    public static function exists(string $fileName):bool {
        return exists($fileName);
    }

    /**
     * @param  string       $fileName
     * @return Unsafe<void>
     */
    public static function delete(string $fileName):Unsafe {
        try {
            deleteFile($fileName);
            return ok();
        } catch (Throwable $e) {
            return error($e);
        }
    }

    /**
     * Open a file.
     * @param string $fileName name of the file to open.
     * @param string $mode     specifies the type of access you require to the stream. It may be any of the following:
     *                         - `r` - Open for reading only; place the file pointer at the beginning of the file.
     *                         - `r+` - Open for reading and writing; place the file pointer at the beginning of the file.
     *                         - `w` - Open for writing only; place the file pointer at the beginning of the file and truncate the file to zero length. If the file does not exist, attempt to create it.
     *                         - `w+` - Open for reading and writing; otherwise it has the same behavior as 'w'.
     *                         - `a` - Open for writing only; place the file pointer at the end of the file. If the file does not exist, attempt to create it. In this mode, [fseek()](https://www.php.net/manual/en/function.fseek.php) has no effect, writes are always appended.
     *                         - `a+` - Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it. In this mode, [fseek()](https://www.php.net/manual/en/function.fseek.php) only affects the reading position, writes are always appended.
     *                         - `x` - Create and open for writing only; place the file pointer at the beginning of the file. If the file already exists, the fopen() call will fail by returning false and generating an error of level E_WARNING. If the file does not exist, attempt to create it. This is equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     *                         - `x+` - Create and open for reading and writing; otherwise it has the same behavior as 'x'.
     *                         - `c` - Open the file for writing only. If the file does not exist, it is created. If it exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails (as is the case with 'x'). The file pointer is positioned on the beginning of the file. This may be useful if it's desired to get an advisory lock (see [flock()](https://www.php.net/manual/en/function.flock.php)) before attempting to modify the file, as using 'w' could truncate the file before the lock was obtained (if truncation is desired, [ftruncate()](https://www.php.net/manual/en/function.ftruncate.php) can be used after the lock is requested).
     *                         - `c+` - Open the file for reading and writing; otherwise it has the same behavior as 'c'.
     *                         - `e` - Set close-on-exec flag on the opened file descriptor. Only available in PHP compiled on POSIX.1-2008 conform systems.
     *
     * > **Note**\
     * > The `mode` is ignored for `php://output`, `php://input`, `php://stdin`, `php://stdout`, `php://stderr` and `php://fd` stream wrappers.
     * @see https://www.php.net/manual/en/function.fopen.php
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

    /**
     * Read and parse the contents of a _.yaml_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @throws Error
     * @return Unsafe
     */
    public static function readYaml(string $interface, string $fileName):Unsafe {
        if (!File::exists($fileName)) {
            $variants = [];

            if (str_ends_with($fileName, '.yml')) {
                $variants[] = substr($fileName, -3).'.yaml';
            } else if (str_ends_with($fileName, '.yaml')) {
                $variants[] = substr($fileName, -5).'.yml';
            } else {
                $variants[] = "$fileName.yaml";
                $variants[] = "$fileName.yml";
            }

            $stringifiedVariants = '';

            foreach ($variants as $variant) {
                if (!str_starts_with($variant, '/') && !str_starts_with($variant, '../') && !str_starts_with($variant, './')) {
                    $variant = "./$variant";
                }

                if (File::exists($variant)) {
                    $fileName = $variant;
                    $file     = File::open($fileName, 'r')->try($error);
                    if ($error) {
                        return error($error);
                    }
                    $contents = $file->readAll()->await()->try($error);
                    if ($error) {
                        return error($error);
                    }

                    $parsed = yaml_parse($contents);
                    if (false === $parsed) {
                        return error("Couldn't parse yaml file.");
                    }
                    return ok((object)$parsed);
                }

                if ($stringifiedVariants) {
                    $stringifiedVariants .= ', ';
                }

                $stringifiedVariants .= "`$variant`";
            }


            return error("Couldn't find yaml file, tried `$fileName` and different variants $stringifiedVariants.");
        }

        $file = File::open($fileName, 'r')->try($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }
        $parsed = yaml_parse($contents);
        if (false === $parsed) {
            return error("Couldn't parse yaml file.");
        }
        return ok((object)$parsed);
    }

    /**
     * Read and parse the contents of a _.json_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @return Unsafe<T>
     */
    public static function readJson(string $interface, string $fileName):Unsafe {
        $file = File::open($fileName, 'r')->try($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }
        $parsed = json_decode($contents, false);
        if (false === $parsed || null === $parsed) {
            return error("Couldn't parse json file.");
        }
        return ok($parsed);
    }

    /**
     * Read and parse the contents of a _.env_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @return Unsafe<T>
     */
    public static function readEnv(string $interface, string $fileName):Unsafe {
        $file = File::open($fileName, 'r')->try($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }
        try {
            $parsed = Dotenv::parse($contents);
        } catch(Throwable $error) {
            return error($error);
        }
        return ok((object)$parsed);
    }

    /**
     * @param AmpFile $ampFile
     * @param string  $mode
     * @param string  $fileName
     */
    private function __construct(
        private AmpFile $ampFile,
        private string $mode,
        public readonly string $fileName,
    ) {
    }

    public function truncate(int $size) {
        $this->ampFile->truncate($size);
    }

    /**
     * @param  string               $content
     * @param  int                  $chunkSize
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
     * @param  ReadableStream       $readableStream
     * @param  int                  $chunkSize
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
     * Set the internal pointer position.
     * @param  int $position
     * @return int
     */
    public function seek(int $position): int {
        return $this->ampFile->seek($position);
    }

    /**
     * Read content from the file in chunks.
     * @param  int                    $length    how much content to read.
     * @param  int                    $chunkSize size of each chunk.
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
     * Read the whole file in chunks.
     * @param  int                    $chunkSize size of each chunk.
     * @return Future<Unsafe<string>>
     */
    public function readAll(int $chunkSize = 8192):Future {
        $fileName = $this->fileName;
        return async(function() use ($fileName, $chunkSize) {
            try {
                $fileSize = getSize($fileName);
                if (0 == $fileSize) {
                    return ok('');
                }
                return $this->read($fileSize, $chunkSize)->await();
            } catch (Throwable $e) {
                return error($e);
            }
        });
    }

    /**
     * @return AmpFile
     */
    public function getAmpFile():AmpFile {
        return $this->ampFile;
    }


    /**
     * @return void
     */
    public function close(): void {
        if ($this->ampFile->isClosed()) {
            return;
        }
        $this->ampFile->close();
    }
}
