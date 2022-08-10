<?php

namespace CatPaw\Utilities;

use Closure;
use function get_class;
use function is_array;
use function is_object;

abstract class Strings {
    public static function red(string $contents):string {
        return "\033[31m $contents";
    }

    public static function yellow(string $contents):string {
        return "\033[33m $contents";
    }

    /**
     * Request an input from the terminal without feeding back to the display whatever it's been typed.
     * @param  string      $prompt message to display along with the input request.
     * @return string|null
     */
    public static function readLineSilent(string $prompt): ?string {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir().'prompt_password.vbs';
            file_put_contents(
                $vbscript,
                'wscript.echo(InputBox("'
                .addslashes($prompt)
                .'", "", "password here"))'
            );
            $command  = "cscript //nologo ".escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return null;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                .addslashes($prompt)
                ."\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
        }
        return $password;
    }

    /**
     * Print an array as an ascii table (recursively).
     * @param  array        $input       the input array.
     * @param  bool         $lineCounter if true a number will be visible for each line inside the ascii table.
     * @param  Closure|null $intercept   intercept the main table and each subtable.<br />
     *                                   This closure will be passed 2 parameters: the AsciiTable and the current depth level.
     * @param  int          $lvl         the depth level will start counting from this value on.
     * @return string       the resulting ascii table.
     */
    public static function tableFromArray(array &$input, bool $lineCounter = false, Closure $intercept = null, int $lvl = 0): string {
        $table = new AsciiTable();
        if (null !== $intercept) {
            $intercept($table, $lvl);
        }
        $table->add("Key", "Value");
        foreach ($input as $key => &$item) {
            if (is_array($item)) {
                $table->add($key, self::tableFromArray($item, $lineCounter, $intercept, $lvl + 1));
                continue;
            } else {
                if (is_object($item)) {
                    $table->add($key, get_class($item));
                    continue;
                }
            }
            $table->add($key, $item);
        }

        return $table->toString($lineCounter);
    }
}
