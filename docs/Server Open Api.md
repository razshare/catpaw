# Open Api

When [mapping routes](./Server%20Router.md), you can use attributes to define Open Api metadata
```php
use CatPaw\Web\Attributes\ProducesItem;

#[ProducesItem(200, 'text/plain', 'Success!', string::class)]
fn() => success('hello')->item();
```

# Available Attributes

| Attribute Name | Description |
|----------------|-------------|
| `Consumes` | Annotate a __route handler__ to describe the content it consumes. |
| `Produces` | Annotate a __route handler__ to describe the content it produces. |
| `ProducesItem` | Annotate a __route handler__ to describe the content it produces using a predefined structure offered by catpaw. |
| `ProducesPage` | Annotate a __route handler__ to describe the content it produces using a predefined paged structure offered by catpaw. |
| `ProducesError` | Annotate a __route handler__ to describe an error. Must be paired with `Produces`. |
| `ProducesItemError` | Annotate a __route handler__ to describe an error. Must be paired with `ProducesItem`. |
| `ProducesPageError` | Annotate a __route handler__ to describe an error. Must be paired with `ProducesPage`. |
| `Summary` | Annotate a __route handler__ with a short description. |
| `Tag` | Annotate a __route handler__ with a tag. SwaggerUi will graphically group routes based on these tags. |
| `Example` | Annotate a __route handler parameter__  to provide an example in SwaggerUi. |
| `Header` | Annotate a __route handler parameter__ to inject an http header. |
| `Param` | Annotate a __route handler parameter__ to inject and configure a path parameter. |
| `Query` | Annotate a __route handler parameter__ to inject a query string. |

# SwaggerUi & Json Metadata

For SwaggerUi to work as intended, you will need to export all this metadata you define through attributes as Json

```php
function main(
  RouterInterface $router,
  OpenApiInterface $oa,
  // ...
){
  $router->get('/openapi', $oa->data(...));
  // ...
}
```

Then feed the data to your SwaggerUi (or any other user interface compliant with Open Api).