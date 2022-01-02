<?php
namespace CatPaw\Tools\Actions;

interface BooleanAction{
    public function run(mixed ...$args):bool;
}