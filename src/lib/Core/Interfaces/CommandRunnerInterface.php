<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return Unsafe<None>
     */
    public function build(CommandBuilder $builder):Unsafe;

    /**
     * Run the command.
     * @return Unsafe<None>
     */
    public function run(CommandContext $context):Unsafe;
}