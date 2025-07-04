<?php

namespace CatPaw\Web\Implementations\ByteRange;

use Amp\Http\Server\Response;
use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\duplex;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use function CatPaw\Core\uuid;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\ByteRangeInterface;
use CatPaw\Web\Interfaces\ByteRangeWriterInterface;
use CatPaw\Web\Mime;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use SplFixedArray;

#[Provider]
readonly class SimpleByteRange implements ByteRangeInterface {
    public function __construct(public LoggerInterface $logger) {
    }

    /**
     *
     * @param  string                                $rangeQuery
     * @return Result<SplFixedArray<array{int,int}>>
     */
    private function parse(string $rangeQuery):Result {
        $rangeQuery = str_replace('bytes=', '', $rangeQuery);
        $ranges     = preg_split('/,\s*/', $rangeQuery);
        $cranges    = count($ranges);
        if (0 === $cranges || '' === trim($ranges[0])) {
            return error("Byte range query does not include any ranges.");
        }

        /** @var SplFixedArray<array{int,int}> */
        $parsedRanges = new SplFixedArray($cranges);

        if (1 === $cranges) {
            $range         = $ranges[0];
            [$start, $end] = explode('-', $range);
            $start         = (int)$start;
            $end           = (int)('' !== $end ? $end : -1);

            $parsedRanges[0] = [$start, $end];
            return ok($parsedRanges);
        }

        for ($i = 0; $i < $cranges; $i++) {
            [$start, $end] = explode('-', $ranges[$i]);
            $start         = (int)$start;
            $end           = (int)('' !== $end ? $end : -1);

            $parsedRanges[$i] = [$start, $end];
        }

        return ok($parsedRanges);
    }

    /**
     *
     * @param  int            $start
     * @param  int            $end
     * @param  int            $contentLength
     * @return array{int,int}
     */
    private function fixClientAmbiguity(int $start, int $end, int $contentLength):array {
        if (-1 === $end) {
            if (0 === $start) {
                // this is chrome
                $end = $contentLength - 1;
            } else if ($start === $contentLength) {
                // this is firefox
                $end = $contentLength;
            } else {
                // this is something else
                $end = $contentLength - 1;
            }
        }

        return [$start,$end];
    }

    /**
     *
     * @param  ByteRangeWriterInterface $interface
     * @return Result<Response>
     */
    public function response(ByteRangeWriterInterface $interface):Result {
        $headers    = [];
        $rangeQuery = $interface->rangeQuery()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $ranges = $this->parse($rangeQuery)->unwrap($error);

        if ($error) {
            return error($error);
        }

        $contentLength = $interface->contentLength()->unwrap($error);
        if ($error) {
            return error($error);
        }

        if ($contentLength < 0) {
            return error("Could not retrieve file size.");
        }

        $contentType = $interface->contentType()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $count = $ranges->count();


        [$reader,$writer] = duplex();

        if (1 === $count) {
            [[$start, $end]]           = $ranges;
            [$start, $end]             = $this->fixClientAmbiguity($start, $end, $contentLength);
            $headers['Content-Length'] = $end - $start + 1;
            $headers['Content-Range']  = "bytes $start-$end/$contentLength";

            $interface->start();

            $response = new Response(
                status: HttpStatus::PARTIAL_CONTENT,
                headers: $headers,
                body: $reader,
            );

            if ($start === $end) {
                $interface->close();
                return ok($response);
            }

            EventLoop::defer(function() use ($writer, $start, $end, $interface) {
                $data = $interface->send($start, $end - $start + 1)->unwrap($error);
                if ($error) {
                    $this->logger->error((string)$error);
                    $writer->close();
                    $interface->close();
                    return;
                }
                $writer->write($data);
                $writer->close();
                $interface->close();
            });

            return ok($response);
        }

        $boundary                = uuid();
        $headers['Content-Type'] = "multipart/byterange; boundary=$boundary";

        $interface->start();

        try {
            $response = new Response(
                status: HttpStatus::PARTIAL_CONTENT,
                headers: $headers,
                body: $reader,
            );
        } catch(InvalidArgumentException $e) {
            return error($e);
        }

        EventLoop::defer(function(string $id) use (
            $writer,
            $interface,
            $ranges,
            $boundary,
            $contentType,
            $contentLength,
        ) {
            foreach ($ranges as $range) {
                [$start, $end] = $range;
                [$start, $end] = $this->fixClientAmbiguity($start, $end, $contentLength);
                $writer->write("--$boundary\r\n");
                $writer->write("Content-Type: $contentType\r\n");
                $writer->write("Content-Range: bytes $start-$end/$contentLength\r\n");

                if ($end < 0) {
                    $end = $contentLength - 1;
                }
                $data = $interface->send($start, $end - $start + 1)->unwrap($error);
                if ($error) {
                    $this->logger->error("Unexpected error in execution callback $id.");
                    $this->logger->error($error);
                    return;
                }
                $writer->write($data);
                $writer->write("\r\n");
            }
            $writer->write("--$boundary--");
            $writer->close();
            $interface->close();
        });

        return ok($response);
    }

    /**
     *
     * @param  string           $fileName
     * @param  string           $rangeQuery
     * @return Result<Response>
     */
    public function file(string $fileName, string $rangeQuery):Result {
        return $this->response(
            interface: new class($rangeQuery, $fileName) implements ByteRangeWriterInterface {
                private File $file;

                public function __construct(
                    private readonly string $rangeQuery,
                    private readonly string $fileName,
                ) {
                }

                public function rangeQuery():Result {
                    return ok($this->rangeQuery);
                }

                public function contentType():Result {
                    return ok(Mime::findContentType($this->fileName));
                }

                public function contentLength():Result {
                    $size = File::size($this->fileName)->unwrap($error) ?? 0;
                    if ($error) {
                        return error($error);
                    }
                    return ok($size);
                }

                /**
                 *
                 * @return Result<None>
                 */
                public function start():Result {
                    $file = File::open($this->fileName)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $this->file = $file;
                    return ok();
                }

                public function send(int $start, int $length):Result {
                    if (!isset($this->file)) {
                        return error("Trying to send payload but the file is not opened.");
                    }
                    $this->file->seek($start);
                    return $this->file->read($length);
                }

                public function close():Result {
                    if (!isset($this->file)) {
                        return error("Trying to close the stream but the file is not opened.");
                    }
                    $this->file->close();
                    return ok();
                }
            }
        );
    }
}
