<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;
use CatPaw\Web\RequestContext;

interface HttpInvokerInterface {
    /**
     * @param  RequestContext   $context
     * @return Unsafe<Response>
     */
    public function invoke(RequestContext $context):Unsafe;
}