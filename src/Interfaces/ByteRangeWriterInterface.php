<?php

namespace CatPaw\Interfaces;

use Amp\Promise;

interface ByteRangeWriterInterface {
	/**
	 * @return Promise
	 */
	public function start(): Promise;

	/**
	 * @param callable $emit
	 * @param int      $start
	 * @param int      $length
	 * @return Promise<void>
	 */
	public function data(callable $emit, int $start, int $length): Promise;


	/**
	 * @return Promise
	 */
	public function end(): Promise;
}