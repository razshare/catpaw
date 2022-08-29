<?php

namespace CatPaw\Utilities;

class AsciiTable {
    private array $rows          = [];
    private int $numberOfCols    = 0;
    private int $globalRowNumber = 1;
    private array $options       = [];
    private int $width           = 0;
    private array $styles        = [];
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

    /**
     * Add a row to the table.
     * @param  string|AsciiCell ...$inputCels
     * @return AsciiTable
     */
    public function add(string|AsciiCell ...$cells):AsciiTable {
        $resultingCells = [];
        for ($i = 0,$end = count($cells) - 1;$i <= $end;$i++) {
            if ($cells[$i] instanceof AsciiCell) {
                $resultingCells[] = $cells[$i];
            } else {
                $resultingCells[] = new AsciiCell($cells[$i], isset($this->styles[$i])?$this->styles[$i]:$this->options);
            }
        }
        $row          = new AsciiRow($this->options, ...$resultingCells);
        $this->rows[] = $row;

        $this->__toString();
        return $this;
    }

    private bool $countLines         = false;
    private bool $countLinesGlobally = false;

    /**
     * Indicates wether or not the resulting stringified table should count lines or not.
     * @param  bool $value
     * @return void
     */
    public function countLines(bool $value) {
        $this->countLines = $value;
    }

    /**
     * Indicates wether or not the resulting stringified table should count lines globally or not.
     * @param  bool $value
     * @return void
     */
    public function countLinesGlobally(bool $value) {
        $this->countLinesGlobally = $value;
    }

    /**
     * Convert to a table to a string.
     * @return string
     */
    public function __toString():string {
        $this->findRowWithMostCells();
        $this->fixWidths();
        $result       = "";
        $numberOfRows = count($this->rows);
        for ($i = 0;$i < $numberOfRows;$i++) {
            if ($i > 0) {
                $result .= preg_replace('/^.+\n/', '', $this->rows[$i]->__toString()).($i + 1 === $numberOfRows?"":"\n");
            } else {
                $result .= $this->rows[$i]->__toString().($i + 1 === $numberOfRows?"":"\n");
                $this->width = \strlen($result);
            }
        }
        if ($this->countLines) {
            $tmp    = preg_split('/\n/', $result);
            $length = count($tmp);
            $result = "";

            $rowNumber = 1;

            for ($i = 0;$i < $length;$i++) {
                if ('|' !== $tmp[$i][0]) {
                    $rowNumber = 1;
                    continue;
                }
                $tmp[$i] = $tmp[$i].($this->countLinesGlobally?" <= [$this->globalRowNumber]":" <= [$rowNumber]");
                $rowNumber++;
                $this->globalRowNumber++;
            }
            $result = implode("\n", $tmp);
        }
        return $result;
    }

    /**
     * Find the row that has most cells.
     * @return void
     */
    private function findRowWithMostCells():void {
        $length = count($this->rows);
        $num    = 0;
        for ($i = 0;$i < $length;$i++) {
            $num = $this->rows[$i]->getNumberOfCels();
            if ($num > $this->numberOfCols) {
                $this->numberOfCols = $num;
            }
        }
    }

    /**
     * Bump all rows to the same width.
     * @return void
     */
    private function fixWidths():void {
        $numberOfCols = $this->numberOfCols;
        $widestCel    = null;
        $cel          = null;
        $width        = null;
        for ($i = 0;$i < $numberOfCols;$i++) {
            $widestCel      = $this->getWidestCellByIndex($i);
            $widestCelWidth = $widestCel->getWidth();
            $numberOfRows   = count($this->rows);
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

    /**
     * Given the `$index` of a cell inside a table find the widest of them all.
     * @param  int       $index
     * @return AsciiCell
     */
    private function getWidestCellByIndex(int $index):AsciiCell {
        $length = count($this->rows);
        $cel    = null;
        for ($i = 0;$i < $length;$i++) {
            if (null === $cel || $cel->getWidth() < $this->rows[$i]->getCel($index)->getWidth()) {
                $cel = $this->rows[$i]->getCel($index);
            }
        }
        return $cel;
    }
}