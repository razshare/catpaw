<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface CommandInterface {
    /**
     * Register a command to run.
     * @param  CommandRunnerInterface $command
     * @return Result<None>
     */
    public function register(CommandRunnerInterface $command):Result;
}