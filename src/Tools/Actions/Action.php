<?php
namespace CatPaw\Tools\Actions;

interface Action{
    public function run(mixed ...$args):void;
}