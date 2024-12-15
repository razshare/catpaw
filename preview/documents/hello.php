<?php
name('hello');

$name ??= input();


if (!mounted()) {
    return;
}
?>

hello <?= $name ?>