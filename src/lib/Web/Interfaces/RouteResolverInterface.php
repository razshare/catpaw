<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Core\Result;

interface RouteResolverInterface {
    /**
     * @param  Request                $request
     * @return Result<false|Response>
     */
    public function resolve(Request $request):Result;
}