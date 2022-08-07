<?php

namespace CatPaw\Amp\File;

use Amp\ByteStream\ClosedException;
use function Amp\call;
use Amp\Failure;
use Amp\File\PendingOperationError;
use Amp\Promise;
use Amp\Success;

final class CatPawFile implements \Amp\File\File {
    /** @var resource */
    private $stream;

    /** @var string */
    private $path;

    /** @var int */
    private $position;

    /** @var int */
    private $size;

    /** @var string */
    private $mode;

    /** @var bool True if an operation is pending. */
    private $busy = false;

    /** @var int Number of pending write operations. */
    private $pendingWrites = 0;

    /** @var bool */
    private $writable = true;

    /** @var Promise|null */
    private $closing;

    /**
     * @param resource $stream
     * @param string   $path
     * @param int      $size
     * @param string   $mode
     */
    public function __construct($stream, string $path, int $size, string $mode) {
        $this->stream   = $stream;
        $this->path     = $path;
        $this->size     = $size;
        $this->mode     = $mode;
        $this->position = 'a' === $this->mode[0] ? $this->size : 0;
    }

    public function __destruct() {
        $this->close();
    }

    public function close(): Promise {
        if ($this->closing) {
            return $this->closing;
        }

        $this->writable = false;

        return call(function() {
            /**
             * @psalm-suppress InvalidPropertyAssignmentValue
             */
            \fclose($this->stream);
            $this->closing = new Success;
        });
    }

    public function truncate(int $size): Promise {
        if ($this->busy) {
            throw new PendingOperationError;
        }

        if (!$this->writable) {
            return new Failure(new ClosedException("The file is no longer writable"));
        }

        return call(function() use ($size) {
            ++$this->pendingWrites;
            $this->busy = true;

            \ftruncate($this->stream, $this->size);

            if (0 === --$this->pendingWrites) {
                $this->busy = false;
            }
        });
    }

    public function eof(): bool {
        return 0 === $this->pendingWrites && $this->size <= $this->position;
    }

    public function read(int $length = self::DEFAULT_READ_LENGTH): Promise {
        if ($this->busy) {
            throw new PendingOperationError;
        }

        return call(function() use ($length) {
            $this->busy = true;

            $data = \fread($this->stream, $length);

            if (false !== $data) {
                $this->position += \strlen($data);
            }
            $this->busy = false;

            return $data?$data:null;
        });
    }

    public function write(string $data): Promise {
        if ($this->busy && 0 === $this->pendingWrites) {
            throw new PendingOperationError;
        }

        if (!$this->writable) {
            return new Failure(new ClosedException("The file is no longer writable"));
        }

        return call(function() use ($data) {
            ++$this->pendingWrites;
            $this->busy = true;
            $wrote      = \fwrite($this->stream, $data);
            $this->position += $wrote;
        
            if (0 === --$this->pendingWrites) {
                $this->busy = false;
            }
            
            return $wrote?$wrote:0;
        });
    }

    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function end(string $data = ""): Promise {
        return call(function() use ($data) {
            $promise        = $this->write($data);
            $this->writable = false;

            // ignore any errors
            yield Promise\any([$this->close()]);

            return $promise;
        });
    }

    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function seek(int $offset, int $whence = SEEK_SET): Promise {
        if ($this->busy) {
            throw new PendingOperationError;
        }

        return call(function() use ($offset, $whence) {
            switch ($whence) {
                case self::SEEK_SET:
                case self::SEEK_CUR:
                case self::SEEK_END:
                    $this->position = \fseek($this->stream, $offset, $whence);

                    if ($this->position > $this->size) {
                        $this->size = $this->position;
                    }
                    return $this->position;
                default:
                    throw new \Error('Invalid whence value. Use SEEK_SET, SEEK_CUR, or SEEK_END.');
            }
        });
    }

    public function tell(): int {
        return $this->position;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getMode(): string {
        return $this->mode;
    }
}
