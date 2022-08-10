<?php

namespace CatPaw\Utilities;

class AsciiRow {
    private $cels = [];
    private $height;
    private $width = 0;
    private $options;
    private $styles = [];
    public function __construct(array &$options, array &$styles, AsciiCel ...$cels) {
        $this->options = $options;
        $this->styles  = $styles;
        $this->cels    = $cels;
        $this->resolveHeight();
        $this->resolveWidth();
    }

    private function resolveHeight():void {
        $result = 0;
        $height = 0;
        foreach ($this->cels as $key => &$cel) {
            $cel->resolve();
        }
        foreach ($this->cels as $key => &$cel) {
            $height = $cel->getHeight();
            if ($height > $result) {
                $result = $height;
            }
        }
        $this->height = $result;
    }

    private function resolveWidth():void {
        $this->width  = 0;
        $numberOfCels = count($this->cels);
        for ($j = 0;$j < $numberOfCels;$j++) {
            $this->width += $this->cels[$j]->getWidth();
        }
    }

    public function getHeight():int {
        return $this->height;
    }

    public function getWidth():int {
        return $this->width;
    }

    public function extendCelBy(int $index, int $width):void {
        $tmp = $this->cels[$index]->increaseWidth($width);
    }

    public function getNumberOfCels():int {
        return count($this->cels);
    }
    public function getCel(int $index):AsciiCel {
        if (!isset($this->cels[$index])) {
            $this->cels[$index] = new AsciiCel("", $this->options);
        }
        return $this->cels[$index];
    }

    private function getLines():array {
        $lines        = [];
        $wholeLines   = [];
        $numberOfCels = count($this->cels);
        for ($j = 0;$j < $numberOfCels;$j++) {
            $numberOfLines = count($this->cels[$j]->getLines());
            if ($numberOfLines < $this->height) {
                $this->cels[$j] = new AsciiCel(
                    $this->cels[$j]->getOriginalString()
                                    .str_repeat("\n", $this->height - $numberOfLines),
                    $this->cels[$j]->getOPtions()
                );
            }
        }


        for ($j = 0;$j < $numberOfCels;$j++) {
            $cel   = $this->cels[$j];
            $lines = $cel->getLines();
            for ($i = 0;$i < $this->height;$i++) {
                if (!isset($wholeLines[$i])) {
                    $wholeLines[$i] = '';
                }
                if (isset($lines[$i])) {
                    $wholeLines[$i] .= '' === $wholeLines[$i]?$lines[$i]:\substr($lines[$i], 1);
                }
            }
        }
        
        return $wholeLines;
    }

    public function toString():string {
        return implode("\n", $this->getLines());
    }
}