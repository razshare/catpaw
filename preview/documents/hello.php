<?php
use CatPaw\Web\Body;
use CatPaw\Web\Query;
$GET  = fn (Query $query) => $query;
$POST = fn (Body $body) => $body;
?>
<?php return function(string $name = 'world'):void { ?>
    <form action="?" method="POST">
        <input type="text" name="name" value="<?=$name?>">
        <button type="submit">Submit</button>
    </form>
<?php } ?>