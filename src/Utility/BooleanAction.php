<?php
namespace CatPaw\Utility;

interface BooleanAction{
    public function run(mixed ...$args):bool;
}