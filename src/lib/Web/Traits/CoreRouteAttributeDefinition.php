<?php
namespace CatPaw\Web\Traits;

use Amp\Http\Server\Request;

trait CoreRouteAttributeDefinition {
    public function onRequest(Request $request): void {
    }

    public function onResult(Request $request, mixed &$result): void {
    }
}