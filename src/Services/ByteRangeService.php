<?php

namespace CatPaw\Services;

use Amp\ByteStream\IteratorStream;
use Amp\Http\InvalidHeaderException;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Producer;
use CatPaw\Attributes\Service;
use CatPaw\Exceptions\InvalidByteRangeQueryException;
use CatPaw\Interfaces\ByteRangeWriterInterface;
use CatPaw\Tools\Strings;
use SplFixedArray;

#[Service]
class ByteRangeService {
	/**
	 * @throws InvalidByteRangeQueryException
	 */
	private function parse(string $rangeQuery): SplFixedArray {
		$rangeQuery = str_replace('bytes=', '', $rangeQuery);
		$ranges = preg_split('/,\s*/', $rangeQuery);
		$cranges = count($ranges);
		if($cranges === 0 || '' === trim($ranges[0]))
			throw new InvalidByteRangeQueryException("Byte range query does not include any ranges.");

		$parsedRanges = new SplFixedArray($cranges);

		if(1 === $cranges) {
			$range = $ranges[0];
			[$start, $end] = explode('-', $range);
			$start = (int)$start;
			$end = (int)($end !== '' ? $end : -1);

			$parsedRanges[0] = [$start, $end];
			return $parsedRanges;
		}

		for($i = 0; $i < $cranges; $i++) {
			[$start, $end] = explode('-', $ranges[$i]);
			$start = (int)$start;
			$end = (int)($end !== '' ? $end : -1);

			$parsedRanges[$i] = [$start, $end];
		}

		return $parsedRanges;
	}

	/**
	 * @throws InvalidByteRangeQueryException
	 * @throws InvalidHeaderException
	 */
	public function response(string $rangeQuery, array $headers, ByteRangeWriterInterface $writer): Response {

		foreach($headers as $key => $value)
			$headers[strtolower(trim($key))] = trim($value);


		if(!isset($headers['content-length']))
			throw new InvalidHeaderException('A byte range response must always set the "content-length" header before streaming the actual content.');

		$contentLength = intval($headers['content-length']);
		unset($headers['content-length']);

		$ranges = $this->parse($rangeQuery);
		$count = $ranges->count();

		if(1 === $count) {
			[[$start, $end]] = $ranges;
			if($end < 0)
				$end = $contentLength - 1;

			$headers['content-length'] = $end - $start + 1;

			$headers['content-range'] = "bytes $start-$end/$contentLength";

			return new Response(
				code          : Status::PARTIAL_CONTENT,
				headers       : $headers,
				stringOrStream: new IteratorStream(
									new Producer(
										function($emit) use ($writer, $start, $end) {
											if($start === $end)
												return;

											yield $writer->start();
											yield $writer->data($emit, $start, $end - $start + 1);
											yield $writer->end();
										}
									)
								)
			);
		}


		$boundary = Strings::uuid();
		$contentType = $headers['content-type']??'text/plain';
		$headers['content-type'] = "multipart/byterange; boundary=$boundary";
		$length = 0;
		foreach($ranges as $r)
			$length += $r[1] - $r[0];

		$headers['content-length'] = $length;
		return new Response(
			code          : Status::PARTIAL_CONTENT,
			headers       : $headers,
			stringOrStream: new IteratorStream(
								new Producer(
									function($emit) use ($writer, $boundary, $ranges, $contentLength, $contentType) {
										yield $writer->start();
										foreach($ranges as $range) {
											[$start, $end] = $range;

											yield $emit("--$boundary\r\n");

											yield $emit("content-type: $contentType\r\n");
											yield $emit("content-range: bytes $start-$end/$contentLength\r\n");

											if($end < 0)
												$end = $contentLength - 1;
											yield $writer->data($emit, $start, $end - $start + 1);
											yield $emit("\r\n");
										}
										yield $emit("--$boundary--");
										yield $writer->end();
									}
								)
							)
		);
	}
}