<?php

namespace CatPaw\Utilities;

class AsciiTable {
    private $rows;
    private $numberOfCols = 0;
    private $masterRow;
    private $globalRowNumber = 1;
    private $options;
    private $width = null;
    private $styles = [];
    public function __construct(array $options = []) {
        $this->options = $options;
    }

    public function style(int $index, array $options):AsciiTable {
        $this->styles[$index] = $options;
        return $this;
    }

    public function getWidth():int {
        return $this->width;
    }

    public function add(...$inputCels):AsciiTable {
        $cels = [];
        for ($i = 0,$end = count($inputCels) - 1;$i <= $end;$i++) {
            if ($inputCels[$i] instanceof AsciiCel) {
                $cels[] = $inputCels[$i];
            } else {
                $cels[] = new AsciiCel($inputCels[$i], isset($this->styles[$i])?$this->styles[$i]:$this->options);
            }
        }
        $row = new AsciiRow($this->options, $this->styles, ...$cels);
        $this->rows[] = $row;

        $this->toString();
        return $this;
    }

    public function toString(bool $countLines = false, bool $globalCountLines = false):string {
        $this->findRowWithMostCels();
        $this->fixWidths();
        $result = "";
        $numberOfRows = count($this->rows);
        for ($i = 0;$i < $numberOfRows;$i++) {
            if ($i > 0) {
                $result .= preg_replace('/^.+\n/', '', $this->rows[$i]->toString()).($i + 1 === $numberOfRows?"":"\n");
            } else {
                $result .= $this->rows[$i]->toString().($i + 1 === $numberOfRows?"":"\n");
                $this->width = \strlen($result);
            }
        }
        if ($countLines) {
            $tmp = preg_split('/\n/', $result);
            $length = count($tmp);
            $result = "";

            $rowNumber = 1;

            for ($i = 0;$i < $length;$i++) {
                if ('|' !== $tmp[$i][0]) {
                    $rowNumber = 1;
                    continue;
                }
                $tmp[$i] = $tmp[$i].($globalCountLines?" <= [$this->globalRowNumber]":" <= [$rowNumber]");
                $rowNumber++;
                $this->globalRowNumber++;
            }
            $result = implode("\n", $tmp);
        }
        return $result;
    }

    private function findRowWithMostCels():void {
        $length = count($this->rows);
        $num = 0;
        for ($i = 0;$i < $length;$i++) {
            $num = $this->rows[$i]->getNumberOfCels();
            if ($num > $this->numberOfCols) {
                $this->numberOfCols = $num;
                $this->masterRow = $this->rows[$i];
            }
        }
    }

    private function fixWidths():void {
        $numberOfCols = $this->numberOfCols;
        $widestCel = null;
        $cel = null;
        $width = null;
        for ($i = 0;$i < $numberOfCols;$i++) {
            $widestCel = $this->getWidestCelByIndex($i);
            $widestCelWidth = $widestCel->getWidth();
            $numberOfRows = count($this->rows);
            for ($j = 0;$j < $numberOfRows;$j++) {
                if (($cel = $this->rows[$j]->getCel($i))) {
                    $width = $widestCelWidth - $cel->getWidth();
                    if ($width > 0) {
                        $this->rows[$j]->extendCelBy($i, $width);
                    }
                }
            }
        }
    }

    private function getWidestCelByIndex(int $index):AsciiCel {
        $length = count($this->rows);
        $cel = null;
        for ($i = 0;$i < $length;$i++) {
            if (null === $cel || $cel->getWidth() < $this->rows[$i]->getCel($index)->getWidth()) {
                $cel = $this->rows[$i]->getCel($index);
            }
        }
        return $cel;
    }
}