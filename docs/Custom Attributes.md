# Custom Attributes

Create the class, annotate it with _#[Attribute]_ and implement _AttributeInterface_

```php
use CatPaw\Core\Result;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use function CatPaw\Core\ok;

#[Attribute]
class HelloWorldAttribute implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Result {
        $value = "hello world";
        return ok();
    }
}

function handler(#[HelloWorldAttribute] string $greeting){
  return $greeting;
}

function main(ServerInterface $server, RouterInterface $router):Result {
  $router->addHandler("GET", "/", handler(...))->unwrap($error);

  if ($error) {
    die($error)
  }

  $server->start()->unwrap($error);

  if ($error) {
    die($error)
  }
}
```

The above code defines an attribute called _#[HelloWorldAttribute]_, which triggers on parameter mount and sets the
value of the parameter to _"hello world"_.

> [!NOTE]
> When the parameter mounts, a new instance of the attribute will be created.
