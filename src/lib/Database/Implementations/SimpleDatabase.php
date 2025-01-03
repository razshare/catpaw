<?php
namespace CatPaw\Database\Implementations;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\env;
use function CatPaw\Core\error;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Database\Interfaces\DatabaseInterface;
use Throwable;


#[Provider]
class SimpleDatabase implements DatabaseInterface {
    /** @var Result<MysqlConnectionPool> */
    private Result $mysqlPoolResult;

    public function __construct() {
        $this->mysqlPoolResult = error("Mysql database pool not initialized.");
    }

    /**
     * @return Result<None>
     */
    #[Entry] public function start():Result {
        try {
            $stringConfiguration = env("mysql");
            
            if (!$stringConfiguration) {
                return error("You're trying to use the MysqlDatabase service, but environment key `mysql` was not found. Consider specifying a MySql connection string using the key `mysql`. The connection string should be formatted as `host=localhost user=username password=password db=test`.");
            }

            $config                = MysqlConfig::fromString($stringConfiguration);
            $this->mysqlPoolResult = ok(new MysqlConnectionPool($config));
            
            return ok();
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @inheritdoc
     */
    public function send(
        string $query,
        array|object $parameters = [],
    ):Result {
        try {
            $pool = $this->mysqlPoolResult->unwrap($error);
            if ($error) {
                return error($error);
            }
    
            if (!is_array($parameters)) {
                $parameters = (array)$parameters;
            }

            $items = $pool->execute($query, $parameters);
            /** @var array<array<string,mixed>> */
            $result = [];
            foreach ($items as $item) {
                $result[] = $item;
            }
    
            return ok($result);
        } catch(Throwable $error) {
            return error($error);
        }
    }
}