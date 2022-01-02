<?php

namespace {

	use Amp\ByteStream\IteratorStream;
	use Amp\File\File;
	use Amp\Http\Server\Response;
	use Amp\Http\Status;
	use Amp\LazyPromise;
	use Amp\Producer;
	use Amp\Promise;
	use CatPaw\Attributes\Http\Produces;
	use CatPaw\Attributes\Http\RequestHeader;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Ranges\Exceptions\InvalidByteRangeQueryException;
	use CatPaw\Ranges\Interfaces\ByteRangeWriterInterface;
	use CatPaw\Ranges\Services\ByteRangeService;
	use CatPaw\Tools\Helpers\Route;
	use function Amp\File\getSize;
	use function Amp\File\openFile;


	#[StartWebServer]
	function main() {

		Route::get('/',
			#[Produces("audio/mp4")]
			function(
				#[RequestHeader("range")] false|array $range,
				ByteRangeService $service
			) {
				$filename = "public/videoplayback.mp4";
				$length = yield getSize($filename);
				try {
					return $service->response(
						rangeQuery: $range[0]??"",
						headers   : [
										"Content-Type"   => "audio/mp4",
										"Content-Length" => $length,
									],
						writer    : new class($filename) implements ByteRangeWriterInterface {
										private File $file;

										public function __construct(private string $filename) { }

										public function start(): Promise {
											return new LazyPromise(function() {
												$this->file = yield openFile($this->filename, "r");
											});
										}


										public function data(callable $emit, int $start, int $length): Promise {
											return new LazyPromise(function() use ($emit, $start, $length) {
												yield $this->file->seek($start);
												$data = yield $this->file->read($length);
												yield $emit($data);
											});
										}


										public function end(): Promise {
											return new LazyPromise(function() {
												yield $this->file->close();
											});
										}
									}
					);
				} catch(InvalidByteRangeQueryException) {
					return new Response(
						code          : Status::OK,
						headers       : [
											"Accept-Ranges"  => "bytes",
											"Content-Type"   => "audio/mp4",
											"Content-Length" => $length,
										],
						stringOrStream: new IteratorStream(
											new Producer(function($emit) use ($filename) {
												/** @var File $file */
												$file = yield openFile($filename, "r");
												while($chunk = yield $file->read(65536)) {
													yield $emit($chunk);
												}
												yield $file->close();
											})
										)
					);
				}
			}
		);
	}
}