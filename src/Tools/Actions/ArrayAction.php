<?php
namespace CatPaw\Tools\Actions;

interface ArrayAction{
    public function run(mixed ...$args):array;
}