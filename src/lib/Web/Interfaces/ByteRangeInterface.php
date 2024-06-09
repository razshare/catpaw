<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;

interface ByteRangeInterface {
    /**
     *
     * @param  ByteRangeWriterInterface $interface
     * @return Unsafe<Response>
     */
    public function response(ByteRangeWriterInterface $interface): Unsafe;

    /**
     *
     * @param  string           $fileName
     * @param  string           $rangeQuery
     * @return Unsafe<Response>
     */
    public function file(string $fileName, string $rangeQuery):Unsafe;
}