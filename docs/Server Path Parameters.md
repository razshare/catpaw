# Path Parameters

You can define path parameters when [mapping routes](./Server%20Router.md).

Any path parameter has two components
1. a path component
2. a route handler component

# Path

Path parameters are wrapped in `{}`
| Path                                                        | Parameters Names Detected            |
|-------------------------------------------------------------|--------------------------------------|
| /`{username}`/about                                         | `username`                           |
| /`{username}`/articles/`{articleId}`                        | `username`, `articleId`              |
| /`{username}`/articles/`{articleId}`/comments               | `username`, `articleId`              |
| /`{username}`/articles/`{articleId}`/comments/`{commentId}` | `username`, `articleId`, `commentId` |
| ...                                                         | ...                                  |

# Route Handler

[Route handlers](./Server%20Router.md) can inject these parameters by name

```php
// PATH: /`{username}`/articles/`{articleId}`/comments/`{commentId}`
function(string $username, string $articleId, string $commendId){};
```

> [!NOTE]
> It is important that the variables names match exactly the parameter names.

# Type safety

Route handlers have the authority to define how parameters are matched.

You can configure this behavior using a `#[Param]` attribute

```php
use CatPaw\Web\Attributes\Param;

// PATH: /`{username}`/articles/`{articleId}`/comments/`{commentId}`
function(
    #[Param('\w{3,}')]    string $username,  // Require $username to be at least 3 characters long.
    #[Param('[A-z0-9-]')] string $articleId, // Require $articleId to include only characters from A to z, numbers and '-'
    #[Param('[A-z0-9-]')] string $commendId, // Require $articleId to include only characters from A to z, numbers and '-'
){};
```