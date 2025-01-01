# Database

Connect to a database and send queries with `DatabaseInterface`.

> [!NOTE]
> Currently only the [Mysql driver](https://amphp.org/mysql) is supported.

You will need to define a connection string in your environment variables (`env.ini`).

```ini
interface = 127.0.0.1:5757
staticsLocation = statics
apiLocation = src/api
mysql="host=localhost user=root password=root db=test"
```

# Raw queries

You can send raw queries

```php
// src/api/{email}/get.php
use CatPaw\Database\Interfaces\DatabaseInterface;

return static fn (DatabaseInterface $db, string $email) => $db->send(
    <<<SQL
        select email, name from users where email = :email
        SQL,
    ['email' => $email]
);
```

# Query builder

You can also use `SqlBuilderInterface` to build and send queries

```php
// src/api/{email}/get.php
use CatPaw\Database\Interfaces\SqlBuilderInterface;

return static fn (SqlBuilderInterface $sql, string $email) => $sql
    ->select('email','name')
    ->from('users')
    ->where()
    ->name('email')
    ->equals()
    ->parameter('email', $email)
    ->one();
```

> [!NOTE]
> In case you're wondering - No, there is no [ORM](https://en.wikipedia.org/wiki/Object%E2%80%93relational_mapping).\
> This project highly discourages the use of ORMs.\
> Raw queries and query builders are encouraged because they are more performant in some cases and they are more explicit, which goes hand in hand with the explicit [error management](./Error%20Management.md) guidelines.