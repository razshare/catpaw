<?php
namespace CatPaw\Core;

use Error;

/**
 * @template T
 */
readonly class Unsafe {
    /**
     * @param T           $value
     * @param false|Error $error
     */
    public function __construct(
        public mixed $value,
        public false|Error $error
    ) {
        if ($error && !($error instanceof Error)) {
            $this->error = new Error($error);
        }
    }

    /**
     * If this `Unsafe` object contains an error returns false and assigns 
     * the error to the `$error` parameter by reference, 
     * otherwise returns itself.
     * 
     * > **Note**\
     * > The reason this method is not hinting it can 
     * > return `false` is so that you keep getting good intellisense
     * > in case there is no error.
     * 
     * Here's an example on how to use this method
     * 
     * ```php
     * $file = File::open('file.txt')->attempt($error) or stop($error);
     * $content = $file->readAll()->attempt($error) or stop($error);
     * echo $content.PHP_EOL;
     * ```
     * @param  Error     $error
     * @return Unsafe<T> or `false` if there is an error.
     */
    public function attempt(&$error) {
        if ($this->error) {
            $error = $this->error;
            return false;
        }

        $error = null;

        return $this;
    }
}