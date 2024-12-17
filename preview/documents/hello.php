<?php return function(string $name = 'world'):void { ?>
    <form action="?" method="get">
        <input type="text" name="name" value="<?=$name?>">
        <button type="submit">Submit</button>
    </form>
<?php } ?>