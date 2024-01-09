<?php

namespace CatPaw\Web\Services;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\Http\Server\Response;
use CatPaw\Attributes\Service;

use function CatPaw\duplex;
use function CatPaw\error;
use CatPaw\File;
use function CatPaw\ok;
use CatPaw\Unsafe;

use function CatPaw\uuid;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\ByteRangeWriterInterface;
use CatPaw\Web\Mime;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Revolt\EventLoop;
use SplFixedArray;

#[Service]
class ByteRangeService {
    /**
     * 
     * @param  string                     $rangeQuery
     * @return Unsafe<SplFixedArray<int>>
     */
    private function parse(string $rangeQuery): Unsafe {
        $rangeQuery = str_replace('bytes=', '', $rangeQuery);
        $ranges     = preg_split('/,\s*/', $rangeQuery);
        $cranges    = count($ranges);
        if (0 === $cranges || '' === trim($ranges[0])) {
            return error("Byte range query does not include any ranges.");
        }

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
     * @param  ByteRangeWriterInterface  $interface
     * @return Unsafe<ResponseInterface>
     */
    public function response(
        ByteRangeWriterInterface $interface
    ): Unsafe {
        $headers           = [];
        $rangeQueryAttempt = $interface->getRangeQuery();
        if ($rangeQueryAttempt->error) {
            return error($rangeQueryAttempt->error);
        }
        $rangeQuery    = $rangeQueryAttempt->value;
        $rangesAttempt = $this->parse($rangeQuery);

        if ($rangesAttempt->error) {
            return error($rangesAttempt->error);
        }

        $contentLengthAttempt = $interface->getContentLength();
        if ($contentLengthAttempt->error) {
            return error($contentLengthAttempt->error);
        }
        $contentLength = $contentLengthAttempt->value;

        if ($contentLength < 0) {
            return error("Could not retrieve file size.");
        }
        
        $contentTypeAttempt = $interface->getContentType();
        if ($contentTypeAttempt->error) {
            return error($contentTypeAttempt->error);
        }
        $contentType = $contentTypeAttempt->value;
        $ranges      = $rangesAttempt->value;
        $count       = $ranges->count();
        

        /** @var ReadableIterableStream $reader */
        /** @var WritableIterableStream $reader */

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

            EventLoop::defer(static function() use ($writer, $start, $end, $interface) {
                $dataAttempt = $interface->send($start, $end - $start + 1);
                if ($dataAttempt->error) {
                    return error($dataAttempt->error);
                }
                $writer->write($dataAttempt->value);
                $writer->close();
                $interface->close();
                return ok();
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

        EventLoop::defer(function() use (
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
                $dataAttempt = $interface->send($start, $end - $start + 1);
                if ($dataAttempt->error) {
                    return error($dataAttempt->error);
                }
                $writer->write($dataAttempt->value);
                $writer->write("\r\n");
            }
            $writer->write("--$boundary--");
            $writer->close();
            $interface->close();
            return ok();
        });

        return ok($response);
    }

    /**
     *
     * @param  string           $fileName
     * @param  string           $rangeQuery
     * @return Unsafe<Response>
     */
    public function file(
        string $fileName,
        string $rangeQuery,
    ):Unsafe {
        return $this->response(
            interface: new class($rangeQuery, $fileName) implements ByteRangeWriterInterface {
                private File $file;

                public function __construct(
                    private readonly string $rangeQuery,
                    private readonly string $fileName,
                ) {
                }

                public function getRangeQuery():Unsafe {
                    return ok($this->rangeQuery);
                }

                public function getContentType():Unsafe {
                    return ok(Mime::findContentType($this->fileName));
                }

                public function getContentLength():Unsafe {
                    $size = File::getSize($this->fileName);
                    if ($size->error) {
                        return error($size->error);
                    }
                    return ok($size->value);
                }

                public function start():Unsafe {
                    $fileAttempt = File::open($this->fileName);
                    if ($fileAttempt->error) {
                        return error($fileAttempt->error);
                    }
                    $this->file = $fileAttempt->value;
                    return ok();
                }

                public function send(int $start, int $length):Unsafe {
                    if (!isset($this->file)) {
                        return error("Trying to send payload but the file is not opened.");
                    }
                    $this->file->seek($start);
                    return $this->file->read($length)->await();
                }

                public function close():Unsafe {
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
