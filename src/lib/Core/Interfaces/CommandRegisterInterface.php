<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface CommandRegisterInterface {
    /**
     * Register a command to run.
     * @param  CommandInterface $command
     * @return Result<None>
     */
    public function register(CommandInterface $command):Result;
}