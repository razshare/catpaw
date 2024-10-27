<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Response;
use CatPaw\Core\Result;

interface ByteRangeInterface {
    /**
     *
     * @param  ByteRangeWriterInterface $interface
     * @return Result<Response>
     */
    public function response(ByteRangeWriterInterface $interface): Result;

    /**
     *
     * @param  string           $fileName
     * @param  string           $rangeQuery
     * @return Result<Response>
     */
    public function file(string $fileName, string $rangeQuery):Result;
}