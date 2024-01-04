<?php

namespace CatPaw\Web\Services;

use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;

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
use Psr\Log\LoggerInterface;

use React\EventLoop\Loop;


use React\Http\Io\ReadableBodyStream;




use React\Http\Message\Response;
use React\Stream\ThroughStream;
use SplFixedArray;

#[Service]
class ByteRangeService {
    private LoggerInterface $logger;

    #[Entry] function start(LoggerInterface $logger) {
        $this->logger = $logger;
        $logger->info("Byte range service initialized.");
    }

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
        $contentType    = $contentTypeAttempt->value;
        $ranges         = $rangesAttempt->value;
        $count          = $ranges->count();
        $throughStream  = new ThroughStream();
        $readableStream = new ReadableBodyStream($throughStream);



        if (1 === $count) {
            $this->logger->info("Serving one single range query.");
            [[$start, $end]] = $ranges;

            [$start, $end] = $this->fixClientAmbiguity($start, $end, $contentLength);

            $headers['Content-Length'] = $end - $start + 1;
            $headers['Content-Range']  = "bytes $start-$end/$contentLength";
            
            $interface->start();

            try {
                $response = new Response(
                    status: HttpStatus::PARTIAL_CONTENT,
                    headers: $headers,
                    body: $readableStream,
                );
            } catch(InvalidArgumentException $e) {
                return error($e);
            }

            if ($start === $end) {
                $interface->close();
                return ok($response);
            }

            Loop::futureTick(static function() use ($throughStream, $readableStream, $start, $end, $interface) {
                $dataAttempt = $interface->send($start, $end - $start + 1);
                if ($dataAttempt->error) {
                    return error($dataAttempt->error);
                }
                $throughStream->write($dataAttempt->value);
                $throughStream->close();
                // $readableStream->close();
                $interface->close();
            });

            return ok($response);
        }

        $this->logger->info("Serving multiple range queries.");

        $boundary                = uuid();
        $headers['Content-Type'] = "multipart/byterange; boundary=$boundary";
        $length                  = 0;
        foreach ($ranges as $r) {
            $length += $r[1] - $r[0];
        }
        
        $interface->start();

        try {
            $response = new Response(
                status: HttpStatus::PARTIAL_CONTENT,
                headers: $headers,
                body: $readableStream,
            );
        } catch(InvalidArgumentException $e) {
            return error($e);
        }

        Loop::futureTick(function() use (
            $throughStream,
            $readableStream,
            $interface,
            $ranges,
            $boundary,
            $contentType,
            $contentLength,
        ) {
            foreach ($ranges as $range) {
                [$start, $end] = $range;

                [$start, $end] = $this->fixClientAmbiguity($start, $end, $contentLength);

                $throughStream->write("--$boundary\r\n");

                $throughStream->write("Content-Type: $contentType\r\n");
                $throughStream->write("Content-Range: bytes $start-$end/$contentLength\r\n");

                if ($end < 0) {
                    $end = $contentLength - 1;
                }
                $dataAttempt = $interface->send($start, $end - $start + 1);
                if ($dataAttempt->error) {
                    return error($dataAttempt->error);
                }
                $throughStream->write($dataAttempt->value);
                $throughStream->write("\r\n");
            }
            $throughStream->write("--$boundary--");
            $throughStream->close();
            // $readableStream->close();
            $interface->close();
        });

        return ok($response);
    }

    /**
     * 
     * @param  string                    $fileName
     * @param  Request                   $request
     * @return Unsafe<ResponseInterface>
     */
    public function file(
        string $fileName,
        string $rangeQuery,
    ):Unsafe {
        return $this->response(
            interface: new class($rangeQuery, $fileName) implements ByteRangeWriterInterface {
                private File $file;

                public function __construct(
                    private string $rangeQuery,
                    private string $fileName,
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
                        return -1;
                    }
                    return ok($size->value);
                }

                public function start():Unsafe {
                    $fileAttempt = File::open($this->fileName, "r");
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
                    if ($error = $this->file->seek($start)->error) {
                        return error($error);
                    }
                    return $this->file->read($length);
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
