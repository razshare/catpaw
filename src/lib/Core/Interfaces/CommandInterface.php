<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\Unsafe;

interface CommandInterface {
    /**
     * Run a command.
     * @param  CommandRunnerInterface $command
     * @return Unsafe<bool>           `true` if the command attempted to run, `false` otherwise.
     */
    public function run(CommandRunnerInterface $command):Unsafe;
}