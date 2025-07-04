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
use function Amp\File\isDirectory;
use function Amp\File\openFile;
use Dotenv\Dotenv;
use Throwable;

readonly class File {
    /**
     * @param  string      $fileName
     * @return Result<int>
     */
    public static function size(string $fileName):Result {
        try {
            return ok(getSize($fileName));
        } catch(Throwable $error) {
            return error("Could not retrieve size of file `$fileName`. $error");
        }
    }

    /**
     *
     * @param  string       $fileNameA
     * @param  string       $fileNameB
     * @param  bool         $binary
     * @return Result<bool>
     */
    public static function checksum(string $fileNameA, string $fileNameB, bool $binary = false):Result {
        $fileA = File::open($fileNameA)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $fileB = File::open($fileNameB)->unwrap($error);
        if ($error) {
            return error($error);
        }


        $contentA = $fileA->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $contentB = $fileB->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $md5A = md5($contentA, $binary);
        $md5B = md5($contentB, $binary);

        return ok($md5A === $md5B);
    }

    /**
     * Copy a file.
     * @param  string       $from
     * @param  string       $to
     * @return Result<None>
     */
    public static function copy(string $from, string $to):Result {
        $source = File::open($from)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $toDirectory = dirname($to);

        if (!File::exists($toDirectory)) {
            Directory::create($toDirectory)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        $dirname = dirname($to);

        // @phpstan-ignore-next-line
        if (false === $dirname) {
            Directory::create($dirname)->unwrap($error);
            if ($error) {
                return $error;
            }
        }

        if (!File::exists($dirname)) {
        }

        $destination = File::open($to, 'x')->unwrap($error);

        if ($error) {
            return error($error);
        }

        $stream = $source->ampFile();

        $destination->writeStream($stream)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $source->close();
        $destination->close();

        return ok();
    }


    /**
     * @param  string               $fileName
     * @return Result<array<mixed>>
     */
    public static function status(string $fileName):Result {
        $status = getStatus($fileName);
        if (null === $status) {
            return error("Could not get status of file $fileName because it doesn't exist.");
        }
        return ok($status);
    }

    /**
     * @param  string      $fileName
     * @return Result<int>
     */
    public static function modificationTime(string $fileName):Result {
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
     * @return Result<None>
     */
    public static function delete(string $fileName):Result {
        try {
            deleteFile($fileName);
            return ok();
        } catch (Throwable $e) {
            return error($e);
        }
    }

    /**
     * Write contents to a file.\
     * If the file doesn't exist it will be created.
     * @param  string       $fileName
     * @return Result<None>
     */
    public static function writeFile(string $fileName, string $contents):Result {
        if (isDirectory($fileName)) {
            return error("`$fileName` is a directory, you cannot write contents directly into a directory.");
        }
        
        $file = File::open($fileName, 'w+')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $file->write($contents)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $file->close();
        return ok();
    }
    
    /**
     * Stream contents to a file.\
     * If the file doesn't exist it will be created.
     * @param  ReadableStream $readableStream
     * @return Result<None>
     */
    public static function writeStreamFile(string $fileName, ReadableStream $readableStream):Result {
        $file = File::open($fileName, 'w+')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $file->writeStream($readableStream)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $file->close();
        return ok();
    }

    /**
     * Read the contents of a file.
     * @param  string         $fileName
     * @return Result<string>
     */
    public static function readFile(string $fileName):Result {
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->unwrap($error) ?? "";
        if ($error) {
            return error($error);
        }
        $file->close();
        return ok($contents);
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
     * @return Result<File>
     */
    public static function open(string $fileName, string $mode = 'r'):Result {
        try {
            $file = openFile($fileName, $mode);
        } catch(Throwable $e) {
            return error($e);
        }
        return ok(new self($file, $mode, $fileName));
    }

    /**
     * Read and parse the contents of a _.ini_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @return Result<T>
     */
    public static function readIni(string $interface, string $fileName):Result {
        $file = File::open($fileName, 'r')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $contents = $file->readAll()->unwrap($error);
        
        if ($error) {
            return error($error);
        }
        
        $parsed = parse_ini_string(ini_string: $contents, process_sections: true);

        if (false === $parsed) {
            return error("Couldn't parse yaml file.");
        }
        /** @var Result<T> */
        return ok((object)$parsed);
    }

    /**
     * Read and parse the contents of a _.yaml_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @return Result<T>
     */
    public static function readYaml(string $interface, string $fileName):Result {
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
                    $file     = File::open($fileName, 'r')->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $contents = $file->readAll()->unwrap($error);
                    if ($error) {
                        return error($error);
                    }

                    $parsed = yaml_parse($contents);
                    if (false === $parsed) {
                        return error("Couldn't parse yaml file.");
                    }
                    /** @var Result<T> */
                    return ok((object)$parsed);
                }

                if ($stringifiedVariants) {
                    $stringifiedVariants .= ', ';
                }

                $stringifiedVariants .= "`$variant`";
            }


            return error("Couldn't find yaml file, tried `$fileName` and different variants $stringifiedVariants.");
        }

        $file = File::open($fileName, 'r')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }
        $parsed = yaml_parse($contents);
        if (false === $parsed) {
            return error("Couldn't parse yaml file.");
        }
        /** @var Result<T> */
        return ok((object)$parsed);
    }

    /**
     * Read and parse the contents of a _.json_ file.
     * @template T
     * @param  class-string<T> $interface Interface of the resulting object.
     * @param  string          $fileName
     * @return Result<T>
     */
    public static function readJson(string $interface, string $fileName):Result {
        $file = File::open($fileName, 'r')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->unwrap($error);
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
     * @return Result<T>
     */
    public static function readEnv(string $interface, string $fileName):Result {
        $file = File::open($fileName, 'r')->unwrap($error);
        if ($error) {
            return error($error);
        }
        $contents = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }
        try {
            $parsed = Dotenv::parse($contents);
        } catch(Throwable $error) {
            return error($error);
        }
        /** @var Result<T> */
        return ok((object)$parsed);
    }

    /**
     * @param AmpFile $ampFile
     * @param string  $mode
     * @param string  $fileName
     */
    private function __construct(
        private AmpFile $ampFile,
        // @phpstan-ignore-next-line
        private string $mode,
        public readonly string $fileName,
    ) {
    }

    public function truncate(int $size):void {
        $this->ampFile->truncate($size);
    }

    /**
     * @param  string       $content
     * @param  int          $chunkSize
     * @return Result<None>
     */
    public function write(string $content, int $chunkSize = 8192):Result {
        try {
            $wroteSoFar = 0;
            $length     = strlen($content);
            while (true) {
                $step  = $wroteSoFar + $chunkSize;
                $chunk = substr($content, $wroteSoFar, $step);
                async(fn () => $this->ampFile->write($chunk))->await();
                $wroteSoFar = $wroteSoFar + $step;
                if ($wroteSoFar >= $length) {
                    return ok();
                }
            }
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReadableStream $readableStream
     * @return Result<None>
     */
    public function writeStream(ReadableStream $readableStream):Result {
        $ampFile = $this->ampFile;
        try {
            while (true) {
                $chunk = async(static fn () => $readableStream->read(null))->await();
                if (null === $chunk) {
                    return ok();
                }
                async(static fn () => $ampFile->write($chunk))->await();
            }
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * Set the internal pointer position.
     * @param  int $position
     * @return int
     */
    public function seek(int $position):int {
        return $this->ampFile->seek($position);
    }

    /**
     * Read content from the file in chunks.
     * @param  int            $length    how much content to read.
     * @param  int            $chunkSize size of each chunk.
     * @return Result<string>
     */
    public function read(int $length = 8192, int $chunkSize = 8192):Result {
        $ampFile = $this->ampFile;
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
    }

    /**
     * Read the whole file in chunks.
     * @param  int            $chunkSize size of each chunk.
     * @return Result<string>
     */
    public function readAll(int $chunkSize = 8192):Result {
        $fileName = $this->fileName;
        try {
            $fileSize = getSize($fileName);
            if (0 == $fileSize) {
                return ok('');
            }
            return $this->read($fileSize, $chunkSize);
        } catch (Throwable $e) {
            return error($e);
        }
    }

    /**
     * @return AmpFile
     */
    public function ampFile():AmpFile {
        return $this->ampFile;
    }


    /**
     * @return void
     */
    public function close():void {
        if ($this->ampFile->isClosed()) {
            return;
        }
        $this->ampFile->close();
    }
}
