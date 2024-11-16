# Database

You can connect to a database and send queries with `DatabaseInterface`.

> [!NOTE]
> Currently only the [Mysql driver](https://amphp.org/mysql) is supported.

You will need to define a connection string in your environment variables (`env.ini`).

```ini
interface = 127.0.0.1:5757
staticsLocation = statics
apiLocation = src/api
mysql="host=localhost user=root password=root db=test"
```
 ```php
// src/api/{email}/get.php
use CatPaw\Database\Interfaces\DatabaseInterface;

return fn (DatabaseInterface $db, string $email) => $db->send(
    <<<SQL
        select email, name from users where email = :email
        SQL,
    ['email' => $email]
);
 ```
