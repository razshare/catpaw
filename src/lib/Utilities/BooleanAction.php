<?php
namespace CatPaw\Utilities;

interface BooleanAction {
    public function run(mixed ...$args):bool;
}