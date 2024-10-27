<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\None;
use CatPaw\Core\Result;

interface CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return Result<None>
     */
    public function build(CommandBuilder $builder):Result;

    /**
     * Run the command.
     * @return Result<None>
     */
    public function run(CommandContext $context):Result;
}