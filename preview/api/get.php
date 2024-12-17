<?php
use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Web\Query;

return fn (DocumentInterface $document, Query $query) => $document->render('hello', $query);