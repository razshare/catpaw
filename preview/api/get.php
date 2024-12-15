<?php
use CatPaw\Document\Interfaces\DocumentInterface;
return static fn (DocumentInterface $doc) => $doc->run('hello', [
    'name' => 'world',
]);