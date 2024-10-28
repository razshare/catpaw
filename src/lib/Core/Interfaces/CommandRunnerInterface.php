<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;

interface CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return void
     */
    public function build(CommandBuilder $builder): void;

    /**
     * Run the command.
     * @return void
     */
    public function run(CommandContext $context): void;
}