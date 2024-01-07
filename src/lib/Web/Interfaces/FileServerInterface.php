<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

interface FileServerInterface {
    public function serve(Request $request):Response;
}