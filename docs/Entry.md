# Entry attribute

The `#[Entry]` attribute is an attribute that can be attached to any service or singleton method.

Any method annotated with `#[Entry]` will be invoked right after the container constructs an instance.\
The method will benefit from dependency injection, just like a constructor.

 ```php
namespace App;

use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\Result;
use function CatPaw\Core\ok;
use function CatPaw\Core\error;

#[Singleton]
class TheWeirdCat {
    #[Entry] public function setup(WeirdCatService $service): Result {
        $theCatBarks = $service->doesTheCatBark();
        if ($theCatBarks) {
            return error('This cat barks');
        }
        return ok();
    }
}

#[Service]
class WeirdCatService {
    public function doesTheCatBark(): bool {
        return true;
    }
}
 ```
