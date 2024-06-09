<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;

interface RouteResolverInterface {
    /**
     * @param  Request                $request
     * @return Unsafe<false|Response>
     */
    public function resolve(Request $request):Unsafe;
}