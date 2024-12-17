<?php
use CatPaw\Web\QueryItem;
?>

<?php expose('/') ?>
<?php function mount(QueryItem $name):void { ?>
    <form action="?" method="get">
        <input type="text" name="name" value="<?=$name->text()?>">
        <button type="submit">Submit</button>
    </form>
<?php } ?>