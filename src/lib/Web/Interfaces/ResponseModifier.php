<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Page;

/**
 * @template T
 * @package CatPaw\Web\Interfaces
 */
interface ResponseModifier {
    public function forText(Response $response):Response;
    /**
     * @return Unsafe<Response>
     */
    public function forJson(Response $response):Unsafe;
    public function forXml(Response $response):Response;
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