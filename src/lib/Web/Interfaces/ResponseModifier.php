<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Unsafe;
use CatPaw\Web\Page;
use Psr\Http\Message\ResponseInterface;

/**
 * @template T
 * @package CatPaw\Web\Interfaces
 */
interface ResponseModifier {
    public function forText(ResponseInterface $response):ResponseInterface;
    /**
     * @return Unsafe<ResponseInterface>
     */
    public function forJson(ResponseInterface $response):Unsafe;
    public function forXml(ResponseInterface $response):ResponseInterface;
    public function withStructure(bool $value = true):void;
    public function isPrimitive():bool;
    public function isPage():bool;
    /**
     * Page the response.
     * @param  Page                    $page
     * @return SuccessResponseModifier
     */
    public function page(Page $page):self;
}