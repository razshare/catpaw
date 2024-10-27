<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Response;
use CatPaw\Core\Result;
use CatPaw\Web\RequestContext;

interface HttpInvokerInterface {
    /**
     * @param  RequestContext   $context
     * @return Result<Response>
     */
    public function invoke(RequestContext $context):Result;
}