<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\Unsafe;

interface CommandInterface {
    /**
     * Register a command to run.
     * @param  CommandRunnerInterface $command
     * @return Unsafe<bool>           `true` if the command attempted to run, `false` otherwise.
     */
    public function register(CommandRunnerInterface $command):Unsafe;
}