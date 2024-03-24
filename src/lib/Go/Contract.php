<?php
namespace CatPaw\Go;

interface Contract {
    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Utilities
    /**
     * Destroy a reference from memory.
     * @param  mixed $key Reference key.
     * @return void
     */
    function destroy(mixed $key):void;
}
