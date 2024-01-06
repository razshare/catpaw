<?php
namespace CatPaw;

use Exception;
use React\Promise\Promise;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

class File {
    /**
     * @param  string      $fileName
     * @return Unsafe<int>
     */
    public static function getSize(string $fileName):Unsafe {
        $size = filesize($fileName);
        if (false === $size) {
            return error("Could not retrieve the size of the file.");
        }

        return ok($size);
    }
    /**
     * Copy a file.
     * @param  string                $from
     * @param  string                $to
     * @return Promise<Unsafe<void>>
     */
    public static function copy(string $from, string $to):Promise {
        $source = File::open($from);
        if ($source->error) {
            return new Promise(static fn ($ok) => $ok(error($source->error)));
        }

        $toDirectory = dirname($to);

        if (!File::exists($toDirectory)) {
            if ($error = Directory::create($toDirectory)->error) {
                return new Promise(static fn ($ok) => $ok(error($error)));
            }
        }

        $destination = File::open($to, 'x');

        if ($destination->error) {
            return new Promise(static fn ($ok) => $ok(error($destination->error)));
        }

        $stream = $source->value->getReadableStream();

        if ($stream->error) {
            return new Promise(static fn ($ok) => $ok(error($stream->error)));
        }

        return $destination->value->writeStream($stream->value);
    }



    /**
     * @return Unsafe<array>
     */
    public static function getStatus(string $fileName):Unsafe {
        $info = stat($fileName);
        if (false === $info) {
            return error("Could not get status of file $fileName.");
        }

        return ok($info);
    }

    /**
     * @return Unsafe<int>
     */
    public static function getModificationTime(string $fileName):Unsafe {
        $mtime = filemtime($fileName);
        if (false === $mtime) {
            return error("Could not find file $fileName modification time.");
        }
        return ok($mtime);
    }

    public static function exists(string $fileName):bool {
        return file_exists($fileName);
    }

    public static function delete(string $fileName):Unsafe {
        if (!unlink($fileName)) {
            return error("Could not delete file $fileName");
        }
        return ok();
    }

    /**
     * @return Unsafe<File>
     */
    public static function open(string $fileName, string $mode = 'r'):Unsafe {
        if (!File::exists($fileName)) {
            return error("File $fileName not found.");
        }
        $file = fopen($fileName, $mode);
        if (!$file) {
            return error("Could not open file $fileName");
        }
        return ok(new self($file));
    }

    private ReadableResourceStream $reader;
    private WritableResourceStream $writer;
    private function __construct(
        private $stream
    ) {
    }


    private function setupReader():Unsafe {
        if (isset($this->reader)) {
            if (!$this->reader->isReadable()) {
                return error("File stream is not readable.");
            }
            return ok();
        }
        $this->reader = new ReadableResourceStream($this->stream);
        return ok();
    }

    private function setupWriter():Unsafe {
        if (isset($this->writer)) {
            if ($this->writer->isWritable()) {
                return error("File stream is not writable.");
            }
            return ok();
        }
        $this->writer = new WritableResourceStream($this->stream);
        return ok();
    }
    
    /**
     * @return Promise<Unsafe<void>>
     */
    public function write(string $content):Promise {
        $setup = $this->setupWriter();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $writer = $this->writer;
        if (is_string($content)) {
            if ($writer->write($content)) {
                return new Promise(static fn ($ok) => $ok(ok()));
            }
            return new Promise(static fn ($ok) => $ok(error("Could not write to file.")));
        }
    }

    /**
     * @return Promise<Unsafe<void>>
     */
    public function writeStream(ReadableResourceStream $reader):Promise {
        $setup = $this->setupWriter();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $writer = $this->writer;

        $resolved = false;
        return new Promise(static function($ok) use ($reader, $writer, &$resolved) {
            $reader->on('data', static function($chunk) use ($writer) {
                $writer->write($chunk);
            });

            $reader->on('end', static function() use ($ok, $reader, &$resolved) {
                $ok(ok());
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('error', static function(Exception $e) use ($ok, $reader, &$resolved) {
                $ok(error($e->getMessage()));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('close', static function() use ($ok, &$resolved) {
                if ($resolved) {
                    return;
                }
                $resolved = true;
                $ok(ok());
            });
        });
    }
    
    /**
     * @return Unsafe<int>
     */
    public function seek(int $position):Unsafe {
        $result = fseek($this->stream, $position);
        if (-1 === $result) {
            return error("Could not feek to $position.");
        }
        return ok($position);
    }

    /**
     * @return Unsafe<string>
     */
    public function read(int $length = 8096):Unsafe {
        $result = fread($this->stream, $length);
        if (false === $result) {
            return error("Could not read from file.");
        }

        return ok($result);
    }
    
    /**
     * @return Promise<Unsafe<string>>
     */
    public function readAll():Promise {
        $setup = $this->setupReader();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $reader   = $this->reader;
        $content  = '';
        $resolved = false;
        return new Promise(static function($ok) use ($reader, &$content, &$resolved) {
            $reader->on('data', static function($chunk) use (&$content) {
                $content .= $chunk;
            });

            $reader->on('end', static function() use ($ok, &$content, $reader, &$resolved) {
                $ok(ok($content));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('error', static function(Exception $e) use ($ok, $reader, &$resolved) {
                $ok(error($e->getMessage()));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('close', static function() use ($ok, &$content, &$resolved) {
                if ($resolved) {
                    return;
                }
                $resolved = true;
                $ok(ok($content));
            });
        });
    }

    /**
     * @return Unsafe<ReadableResourceStream>
     */
    public function getReadableStream():Unsafe {
        if ($error = $this->setupReader()->error) {
            return error($error);
        }
        return ok($this->reader);
    }

    /**
     * @return Unsafe<WritableResourceStream>
     */
    public function getWritableStream():Unsafe {
        if ($error = $this->setupWriter()->error) {
            return error($error);
        }
        return ok($this->writer);
    }

    public function close() {
        if (isset($this->reader)) {
            $this->reader->close();
        }
        if (isset($this->writer)) {
            $this->writer->close();
        }
    }
}
