<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\None;
use CatPaw\Core\Result;

interface CommandInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return void
     */
    public function build(CommandBuilder $builder):void;

    /**
     * Run the command.
     * @return Result<None>
     */
    public function run(CommandContext $context):Result;
}