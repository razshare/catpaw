# RaspberryPi

You can interact with your RaspberryPi's Gpio interface through `GpioInterface`.<br/>

1. Use `$gpio->createReader()` to create a pin reader.
2. Use `$gpio->createWriter()` to create a pin writer.

Both methods take one argument, the pin.

The pin is a string that can have one of the following
values: _7_, _11_, _12_, _13rv1_, _13_, _13rv2_, _15_, _16_, _18_, _22_.\
Pin _13_ is an alias for _13rv2_, meaning internally its index is resolved to _27_.

As you can see they're named following the pins' indexing as show in this schema:
![gpio1](https://user-images.githubusercontent.com/6891346/152225115-782f0313-d525-4d5f-9b5c-cecd32fdd865.png)

This way you can physically count the pins' position if you don't have the schema around and don't remember it.

Here's an example of a blinking LED on pin _12_<br/>
![image](https://user-images.githubusercontent.com/6891346/152228030-7d1f5cba-6308-42be-bc14-c62df1a81554.png)

```php
<?php
use function Amp\delay;
use function CatPaw\Core\error;
use function CatPaw\Core\anyError;
use CatPaw\RaspberryPi\Interfaces\GpioInterface;

function main(GpioInterface $gpio): void {
    $writer = $gpio->createWriter('12');
    $active = true;
    while (true) {
        $writer->write($active?'1':'0')->try();
        $active = !$active;
        delay(1);
    }
}
```

![ezgif-7-8019444815](https://user-images.githubusercontent.com/6891346/152222230-e504eaa4-e014-4c91-ae56-3d4376b1d3d2.gif)
