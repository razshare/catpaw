<?php
namespace CatPaw\Web\Interfaces;

interface FileServerOverwriteInterface {
    function overwrite(string $fileName, string $path):string;
}