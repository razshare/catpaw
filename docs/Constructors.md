# Constructors

As long as a class is annotated with `#[Provider]`, the constructor can request services directly.

 ```php
namespace App;

use CatPaw\Core\Attributes\Provider;

#[Provider] class Cat {
    public function saySomething():void {
        echo "meow";
    }
}

#[Provider] class CatOwner {
    public function __construct(Cat $cat) {
        $cat->saySomething();
    }
}
 ```
