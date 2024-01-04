<?php
namespace CatPaw\Web\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface FileServerInterface {
    public function serve(RequestInterface $request):ResponseInterface;
}