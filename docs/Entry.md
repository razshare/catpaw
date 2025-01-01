# Entry attribute

The `#[Entry]` attribute is an attribute that can be attached to any method of any `#[Provider]` class.

Methods annotated with `#[Entry]` will be invoked right after the container constructs an instance.\
These methods can request services directly.

 ```php
namespace App;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Result;
use function CatPaw\Core\ok;
use function CatPaw\Core\error;

interface Cat {
    public function saySomething():void;
    public function doesTheCatBark():bool;
}

#[Provider] class WeirdCat implements Cat {
    public function saySomething():void {
        echo "woof";
    }
    public function doesTheCatBark():bool {
        return true;
    }
}

#[Provider] class CatOwner {
    /**
     * @param Cat $cat
     * @return Result<None>
     */
    #[Entry] public function setup(Cat $cat):Result {
        if ($cat->doesTheCatBark()) {
            return error('You gave me a cat that barks');
        }
        
        $cat->saySomething();

        return ok();
    }
}
 ```

Unlike [constructors](./Constructors.md), entry methods can return `Result<None>` objects, which will be managed by the [container](./Container.md).