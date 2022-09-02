<?php

namespace CatPaw\Utilities;

/**
 * @psalm-type Options = array{
 *  width: int,
 *  padding-left: int,
 *  padding-right: int,
 *  padding-top: int,
 *  padding-bottom: int,
 *  padding-between-lines-top: int,
 *  padding-between-lines-bottom: int
 * }
 * @package CatPaw\Utilities
 */
class AsciiCell {
    /**
     * Create a cell from a string.
     * @param  string    $value
     * @param  array     $options
     * @return AsciiCell
     */
    public static function fromString(string $value):self {
        return new self($value);
    }


    private int $width             = 0;
    private int $height            = 0;
    private int|string $top        = 0;
    private int|string $bottom     = 0;
    private array $lines           = [];
    private string $empty          = '';
    private string $originalString = '';
    
    /** @var Options */
    private array $options = [
        "width"                        => 4096,
        "padding-left"                 => 1,
        "padding-right"                => 1,
        "padding-top"                  => 0,
        "padding-bottom"               => 0,
        "padding-between-lines-top"    => 0,
        "padding-between-lines-bottom" => 0
    ];

    /**
     * Get the options of the cell.
     * @return Options
     */
    public function &getOptions():mixed {
        return $this->options;
    }
    
    private function __construct(private string $data) {
        $this->setOptions([]);
    }

    public function setOptions(array $options):self {
        $data                 = $this->data;
        $data                 = \preg_replace("/\\t/", \str_repeat(" ", 4), $data);
        $this->originalString = $data;
        foreach ($options as $key => &$value) {
            $this->options[$key] = $value;
        }
        $this->parseOptions();
        $this->lines = [];
        $lines       = preg_split('/\n/', $data);
        
        for ($i = 0,$end = count($lines) - 1;$i <= $end;$i++) {
            if ($this->options["width"] < strlen($lines[$i])) {
                $lines[$i] = str_split($lines[$i], $this->options["width"]);
            }
        }

        $flatten = [];
        //flatten $lines array
        array_walk_recursive($lines, function($a) use (&$flatten) { $flatten[] = $a; });
        $lines = $flatten;

        $length = 0;
        for ($i = 0,$end = count($lines) - 1;$i <= $end; $i++) {
            $length        = strlen($lines[$i]);
            $this->lines[] = $lines[$i];
            if ($this->width < $length) {
                $this->width = $length;
            }
        };

        return $this;
    }

    public function getHeight():int {
        return $this->height;
    }

    public function getWidth():int {
        return $this->width;
    }

    public function setWidth(int $width):void {
        $this->width = $width;
    }

    public function increaseWidth(int $width):void {
        $this->width += $width;
    }

    public function decreaseWidth(int $width):void {
        $this->width -= $width;
    }

    public function getLines():array {
        return $this->resolve();
    }

    private function parseOptions():void {
        foreach ($this->options as $key => &$value) {
            switch ($key) {
                case "padding":
                    $this->options["padding-left"]   = $value;
                    $this->options["padding-right"]  = $value;
                    $this->options["padding-top"]    = $value;
                    $this->options["padding-bottom"] = $value;
                    break;
                case "padding-between-lines":
                    $this->options["padding-between-lines-left"]   = $value;
                    $this->options["padding-between-lines-right"]  = $value;
                    $this->options["padding-between-lines-top"]    = $value;
                    $this->options["padding-between-lines-bottom"] = $value;
                    break;
            }
        }
    }

    public function getOriginalString():string {
        return $this->originalString;
    }

    public function resolve():array {
        $tmp    = [];
        $length = count($this->lines);
        for ($i = 0;$i < $length;$i++) {
            $dataLen = strlen($this->lines[$i]);
            if ($this->width < $dataLen) {
                $this->width = $dataLen;
            }
        }
        
        $this->top    = str_repeat("-", $this->width);
        $this->bottom = str_repeat("-", $this->width);
        $this->empty  = str_repeat(" ", $this->width);

        $this->insertLineInTmp($this->top, $tmp, "+", true, true);
        for ($j = 0;$j < $this->options["padding-top"];$j++) {
            $this->insertLineInTmp($this->empty, $tmp, "|", true, true);
        }
        for ($i = 0;$i < $length;$i++) {
            for ($j = 0;$j < $this->options["padding-between-lines-top"];$j++) {
                $this->insertLineInTmp($this->empty, $tmp, "|", true, true);
            }
            $this->insertLineInTmp($this->lines[$i], $tmp);
            for ($j = 0;$j < $this->options["padding-between-lines-bottom"];$j++) {
                $this->insertLineInTmp($this->empty, $tmp, "|", true, true);
            }
        }
        for ($j = 0;$j < $this->options["padding-bottom"];$j++) {
            $this->insertLineInTmp($this->empty, $tmp, "|", true, true);
        }
        $this->insertLineInTmp($this->bottom, $tmp, "+", true, true);
        return $tmp;
    }

    private function insertLineInTmp(string $data, array &$tmp, string $sideString = "|", bool $extendFirstCharacter = false, bool $extendRightCharacter = false):void {
        if (\preg_match("/\\n/", $data)) {
            $split = preg_split("/\\n/", $data);
            foreach ($split as &$extraRowData) {
                $this->insertLineInTmp($extraRowData, $tmp, $sideString, $extendFirstCharacter, $extendRightCharacter);
            }
            return;
        }
        $paddingLeft  = str_repeat(isset($data[0]) && $extendFirstCharacter?$data[0]:" ", $this->options["padding-left"]);
        $paddingRight = str_repeat(isset($data[-1]) && $extendRightCharacter?$data[-1]:" ", $this->options["padding-right"]);
        $len          = strlen($data);
        if ($len > $this->width) {
            $this->width = $len;
        } else {
            if ($len < $this->width) {
                $data .= str_repeat(" ", $this->width - $len);
            }
        }
        $tmp[] = $sideString.$paddingLeft.$data.$paddingRight.$sideString;
        $this->height++;
    }
}