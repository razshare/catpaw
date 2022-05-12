<?php
namespace CatPaw\Utilities;

interface ArrayAction{
    public function run(mixed ...$args):array;
}