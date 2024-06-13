# Custom Attributes

Create the class, annotate it with _#[Attribute]_ and implement _AttributeInterface_

```php
<?php
use CatPaw\Core\Unsafe;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use function CatPaw\Core\anyError;
use function CatPaw\Core\ok;

#[Attribute]
class HelloWorldAttribute implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options) : Unsafe {
        $value = "hello world";
        return ok();
    }
}

function handler(#[HelloWorldAttribute] string $greeting){
  return $greeting;
}

function main(ServerInterface $server, RouterInterface $router): Unsafe {
  return anyError(function() use($server) {
    $router->get("/", handler(...))->try();
    $server->start()->try();
  });
}
```

The above code defines an attribute called _#[HelloWorldAttribute]_, which triggers on parameter mount and sets the
value of the parameter to _"hello world"_.

> [!NOTE]
> When the parameter mounts, a new instance of the attribute will be created.
