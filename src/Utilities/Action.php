<?php
namespace CatPaw\Utility;

interface Action{
    public function run(mixed ...$args):void;
}