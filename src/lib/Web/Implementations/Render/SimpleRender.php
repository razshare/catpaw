<?php
namespace CatPaw\Web\Implementations\Render;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Web\Interfaces\RenderInterface;
use CatPaw\Web\Interfaces\ResponseModifierInterface;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;

#[Provider(singleton:false)]
class SimpleRender implements RenderInterface {
    private string $data = '';
    private bool $open   = false;

    public function start():void {
        $this->open = true;
        ob_start();
    }

    public function stop():void {
        $response = ob_get_contents();
        ob_end_clean();
        $this->open = false;
        if (false !== $response) {
            $this->data .= $response;
        }
    }

    public function response():ResponseModifierInterface {
        if ($this->open) {
            $this->stop();
        }

        return success($this->data)->as(TEXT_HTML);
    }
}