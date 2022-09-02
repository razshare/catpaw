<?php

namespace CatPaw\Utilities;

class AsciiRow {
    public static function fromCells(AsciiCell ...$cells): self {
        return new self(...$cells);
    }

    private array $cells   = [];
    private int $height    = 0;
    private int $width     = 0;
    private array $options = [];
    private function __construct(AsciiCell ...$cells) {
        $this->cells = $cells;
        $this->resolveHeight();
        $this->resolveWidth();
    }

    public function setOptions(array $options):self {
        $this->options = $options;
        return $this;
    }

    private function resolveHeight():void {
        $result = 0;
        $height = 0;
        foreach ($this->cells as $key => &$cel) {
            $cel->resolve();
        }
        foreach ($this->cells as $key => &$cel) {
            $height = $cel->getHeight();
            if ($height > $result) {
                $result = $height;
            }
        }
        $this->height = $result;
    }

    private function resolveWidth():void {
        $this->width  = 0;
        $numberOfCels = count($this->cells);
        for ($j = 0;$j < $numberOfCels;$j++) {
            $this->width += $this->cells[$j]->getWidth();
        }
    }

    public function getHeight():int {
        return $this->height;
    }

    /**
     * Get the width of the row.
     * @return int
     */
    public function getWidth():int {
        return $this->width;
    }

    /**
     * Extend the length of a cell  `$index` inside the row by `$width` characters.
     * @param  int  $index
     * @param  int  $width
     * @return void
     */
    public function extendCelBy(int $index, int $width):void {
        $tmp = $this->cells[$index]->increaseWidth($width);
    }

    /**
     * Get the number of cells.
     * @return int
     */
    public function getNumberOfCels():int {
        return count($this->cells);
    }

    /**
     * Get a cell from the row.
     * @param  int       $index
     * @return AsciiCell
     */
    public function getCell(int $index):AsciiCell {
        if (!isset($this->cells[$index])) {
            $this->cells[$index] = AsciiCell::fromString("")->setOptions($this->options);
        }
        return $this->cells[$index];
    }

    /**
     * Get the lines from the virtual "row".
     * @return array
     */
    private function getLines():array {
        $lines        = [];
        $wholeLines   = [];
        $numberOfCels = count($this->cells);
        for ($j = 0;$j < $numberOfCels;$j++) {
            $numberOfLines = count($this->cells[$j]->getLines());
            if ($numberOfLines < $this->height) {
                $this->cells[$j] = AsciiCell::fromString(
                    $this->cells[$j]->getOriginalString().str_repeat("\n", $this->height - $numberOfLines),
                )->setOptions($this->cells[$j]->getOPtions());
            }
        }


        for ($j = 0;$j < $numberOfCels;$j++) {
            $cel   = $this->cells[$j];
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

    /**
     * Convert the row to a string.
     * @return string
     */
    public function __toString():string {
        return implode("\n", $this->getLines());
    }
}