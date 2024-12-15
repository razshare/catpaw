<?php
use CatPaw\Web\Interfaces\SessionInterface;
use function CatPaw\Web\success;

return function(SessionInterface $session) {
    $counter = &$session->ref('counter', 0);
    return success($counter++);
};
