<?php
namespace CatPaw\Web\Traits;

use React\Http\Message\Request;

trait CoreRouteAttributeDefinition {
    public function onRequest(Request $request): void {
    }

    public function onResult(Request $request, mixed &$result): void {
    }
}