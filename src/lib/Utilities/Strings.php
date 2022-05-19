<?php

namespace CatPaw\Utilities;

use Closure;
use function get_class;
use function is_array;
use function is_object;

abstract class Strings {
    const PATTERN_JS_ESCAPE_LEFT_START = "<\\s*(?=script)";
    const PATTERN_JS_ESCAPE_LEFT_END = "<\\s*\\/\\s*(?=script)";
    const PATTERN_JS_ESCAPE_RIGHT_START1 = "?<=(\\&lt\\;script)\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_START2 = "?<=(\\&lt\\;script).*\\s*>";
    const PATTERN_JS_ESCAPE_RIGHT_END = "?<=(&lt;\\/script)>";


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
				$vbscript, 'wscript.echo(InputBox("'
				.addslashes($prompt)
				.'", "", "password here"))');
            $command = "cscript //nologo ".escapeshellarg($vbscript);
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
     * Compress data using "deflate" or "gzip".
     * @param  string     $type     type of compression used.
     *                              Can be "deflate" or "gzip".
     * @param  string     $data     data The data to encode.
     * @param  array      $order    the order in which to attempt compression.
     * @param  array|null $accepted accepted types of compression.
     * @return true       if $data was compressed, false otherwise.
     */
    public static function compress(string &$type, string &$data, array $order = ["deflate", "gzip"], array $accepted = null): bool {
        if (null === $accepted) {
            $type = "deflate";
            $data = gzdeflate($data);
            return false;
        } else {
            $len = count($order);
            for ($i = 0; $i < $len; $i++) {
                if (in_array($order[$i], $accepted)) {
                    $type = $order[$i];
                    switch ($order[$i]) {
						case "deflate":
							$data = gzdeflate($data);
							break;
						case "gzip":
							$data = gzcompress($data);
							break;
						default:
							return false;
					}
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Escape javascript tags from an HTML string.
     * @param  string $content content the input string.
     * @return string the escaped string.
     */
    public static function escapeJs(string $content): string {
        return
			preg_replace(self::PATTERN_JS_ESCAPE_LEFT_START, "&lt;",
				preg_replace(self::PATTERN_JS_ESCAPE_LEFT_END, "&lt;/",
					preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_END, "&gt;",
						preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START1, "&gt;",
							preg_replace(self::PATTERN_JS_ESCAPE_RIGHT_START2, "&gt;", $content)
						)
					)
				)
			);
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

    /**
     * Generate a universally unique identifier
     * @return string the uuid.
     */
    public static function uuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
    }
}
