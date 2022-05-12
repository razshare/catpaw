<?php
namespace CatPaw\Utility;

interface ArrayAction{
    public function run(mixed ...$args):array;
}