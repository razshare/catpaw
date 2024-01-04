<?php

namespace CatPaw\Web\Services;

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

use function React\Async\async;
use React\Http\Io\ReadableBodyStream;

use React\Http\Message\Response;
use React\Stream\ThroughStream;
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
     * @param  ByteRangeWriterInterface $interface
     * @return Unsafe<Response>
     */
    public function response(
        ByteRangeWriterInterface $interface
    ): Unsafe {
        $response      = new Response();
        $headers       = [];
        $rangeQuery    = $interface->getRangeQuery();
        $contentLength = $interface->getContentLength();

        if ($contentLength < 0) {
            return error("Could not retrieve file size.");
        }

        $contentType = $interface->getContentType();

        $rangesAttempt = $this->parse($rangeQuery);

        if ($rangesAttempt->error) {
            return error($rangesAttempt->error);
        }

        $ranges = $rangesAttempt->value;

        $count = $ranges->count();

        $through = new ThroughStream();
        
        if (1 === $count) {
            [[$start, $end]] = $ranges;

            [$start, $end] = $this->fixClientAmbiguity($start, $end, $contentLength);

            $headers['Content-Length'] = $end - $start + 1;
            $headers['Content-Range']  = "bytes $start-$end/$contentLength";


            $interface->start();

            async(static function() use ($start, $end, $through, $interface) {
                if ($start === $end) {
                    $interface->end();
                    return;
                }
                $interface->send($through->write(...), $start, $end - $start + 1);
                $interface->end();
            })();

            try {
                $response->withStatus(HttpStatus::PARTIAL_CONTENT);
                foreach ($headers as $key => $value) {
                    $response->withHeader($key, $value);
                }
                $response->withBody(new ReadableBodyStream($through));
                return ok($response);
            } catch(InvalidArgumentException $e) {
                return error($e);
            }
        }


        $boundary                = uuid();
        $headers['Content-Type'] = "multipart/byterange; boundary=$boundary";
        $length                  = 0;
        foreach ($ranges as $r) {
            $length += $r[1] - $r[0];
        }
        
        $interface->start();

        async(function() use (
            $through,
            $interface,
            $ranges,
            $boundary,
            $contentType,
            $contentLength,
        ) {
            foreach ($ranges as $range) {
                [$start, $end] = $range;

                [$start, $end] = $this->fixClientAmbiguity($start, $end, $contentLength);

                $through->write("--$boundary\r\n");

                $through->write("Content-Type: $contentType\r\n");
                $through->write("Content-Range: bytes $start-$end/$contentLength\r\n");

                if ($end < 0) {
                    $end = $contentLength - 1;
                }
                $interface->send($through->write(...), $start, $end - $start + 1);
                $through->write("\r\n");
            }
            $through->write("--$boundary--");
            $interface->end();
        })();

        try {
            $response->withStatus(HttpStatus::PARTIAL_CONTENT);
            foreach ($headers as $key => $value) {
                $response->withHeader($key, $value);
            }
            $response->withBody(new ReadableBodyStream($through));
            return ok($response);
        } catch(InvalidArgumentException $e) {
            return error($e);
        }
    }

    /**
     * 
     * @param  string           $fileName
     * @param  Request          $request
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
                    private string $rangeQuery,
                    private string $fileName,
                ) {
                }

                public function getRangeQuery(): string {
                    return $this->rangeQuery;
                }

                public function getContentType(): string {
                    return Mime::findContentType($this->fileName);
                }

                public function getContentLength(): int {
                    $size = File::getSize($this->fileName);
                    if ($size->error) {
                        return -1;
                    }
                    return $size->value;
                }

                public function start():void {
                    $this->file = File::open($this->fileName, "r");
                }

                public function send(callable $emit, int $start, int $length): void {
                    $this->file->seek($start);
                    $data = $this->file->read($length);
                    if ($data->error) {
                        return;
                    }
                    $emit($data);
                }

                public function end(): void {
                    $this->file->close();
                }
            }
        );
    }
}
