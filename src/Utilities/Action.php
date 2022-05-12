<?php
namespace CatPaw\Utilities;

interface Action{
    public function run(mixed ...$args):void;
}