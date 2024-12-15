<?php
use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Web\Query;

return static fn (DocumentInterface $doc, Query $name) => $doc->run('hello', [
    'name' => $name->text(),
]);