# Constructors

As long as a class is annotated with `#[Provider]`, the constructor can request services directly.

 ```php
namespace App;

use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\Result;
use function CatPaw\Core\ok;
use function CatPaw\Core\error;

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
