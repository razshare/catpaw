# Entry attribute

The `#[Entry]` attribute is an attribute that can be attached to any method of any `#[Provider]` class.

Methods annotated with `#[Entry]` will be invoked right after the container constructs an instance.\
These methods can request services directly.

 ```php
namespace App;

use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\Result;
use function CatPaw\Core\ok;
use function CatPaw\Core\error;

interface CatInterface {
    public function doesTheCatBark():bool;
}

#[Provider]
class WeirdCat implements CatInterface {
    public function doesTheCatBark():bool {
        return true;
    }
}

#[Provider]
class CatOwner {
    #[Entry]
    public function setup(CatInterface $cat):Result {
        if ($cat->doesTheCatBark()) {
            return error('You gave me a cat that barks');
        }
        return ok();
    }
}
 ```
