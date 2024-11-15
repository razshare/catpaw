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
use Throwable;

#[Provider(singleton:true)]
class MysqlDatabase {
    private MysqlConnectionPool|false $pool = false;

    /**
     * @return Result<None>
     */
    #[Entry] public function start():Result {
        try {
            $stringConfiguration = env("mysql");
            
            if (!$stringConfiguration) {
                return error("You're trying to use the MysqlDatabase service, but environment key `mysql` was not found. Consider specifying a MySql connection string using the key `mysql`. The connection string should be formatted as `host=localhost user=username password=password db=test`.");
            }

            $config     = MysqlConfig::fromString($stringConfiguration);
            $this->pool = new MysqlConnectionPool($config);
            
            return ok();
        } catch(Throwable $error) {
            return error($error);
        }
    }

    public function pool():MysqlConnectionPool {
        return $this->pool;
    }
}