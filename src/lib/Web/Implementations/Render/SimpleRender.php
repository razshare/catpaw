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

    public function start():self {
        $this->open = true;
        ob_start();
        return $this;
    }

    public function stop():self {
        $response = ob_get_contents();
        ob_end_clean();
        $this->open = false;
        if (false !== $response) {
            $this->data .= $response;
        }
        return $this;
    }

    public function response():ResponseModifierInterface {
        if ($this->open) {
            $this->stop();
        }

        return success($this->data)->as(TEXT_HTML);
    }
}