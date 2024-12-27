<?php
namespace Tests;

use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\FileName;
use CatPaw\Core\Implementations\Command\SimpleCommand;
use CatPaw\Core\Interfaces\CommandInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Database\Implementations\SimpleSqlBuilder;
use CatPaw\Database\Interfaces\DatabaseInterface;
use CatPaw\Database\Interfaces\SqlBuilderInterface;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    private MemoryDatabase $db;

    public function testAll():void {
        $this->db = new MemoryDatabase;
        Container::provide(CommandInterface::class, new SimpleCommand);
        Container::provide(DatabaseInterface::class, $this->db);
        Container::requireLibraries(FileName::create(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makeSureSelectWorks(...));
            yield Container::run($this->makeSureUpdateWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
    }


    private function makeSureSelectWorks(SqlBuilderInterface $sql):void {
        $account = $sql
            ->select()
            ->from('accounts')
            ->where()
            ->name('email')
            ->equals()
            ->parameter('email', 'weird@barking.cat')
            ->one(Account::class)
            ->unwrap($error);

        $this->assertNull($error);
        $this->assertNotFalse($account);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('weird@barking.cat', $account->email);
        $this->assertEquals('cat', $account->name);

        $query = $this->db->query();
        $this->assertEquals("select * from accounts where email = :email", trim($query));

        $parameters = $this->db->parameters();
        $this->assertArrayHasKey('email', $parameters);
        $this->assertEquals('weird@barking.cat', $parameters['email']);
    }


    private function makeSureUpdateWorks(SqlBuilderInterface $sql):void {
        $account        = new Account;
        $account->email = 'test@test.test';
        $account->name  = 'test';

        $sql->update('accounts')->set($account)->where()->name('email')->equals()->parameter('email', 'weird@barking.cat')->none()->unwrap($error);
        $this->assertNull($error);

        $query = $this->db->query();
        $this->assertStringStartsWith("update accounts set email = :email_", trim($query));
        $this->assertStringEndsWith(" where email = :email", trim($query));
    }
}

class Account {
    public string $email;
    public string $name;
}

class MemoryDatabase implements DatabaseInterface {
    private string $query = '';
    public function query():string {
        return $this->query;
    }

    /** @var array<string,mixed> */
    private array $parameters = [];

    /**
     * @return array<string,mixed>
     */
    public function parameters():array {
        return $this->parameters;
    }
    /**
     * Send a query to the database.
     * @param  string                             $query
     * @param  array<string,mixed>|object         $parameters
     * @return Result<array<array<string,mixed>>>
     */
    public function send(string $query, array|object $parameters = []):Result {
        $this->query      = $query;
        $this->parameters = $parameters;
        // @phpstan-ignore return.type
        return ok([
            [
                'email' => 'weird@barking.cat',
                'name'  => 'cat',
            ],
        ]);
    }

    /**
     * Create a sql builder.
     * @return SqlBuilderInterface
     */
    public function builder():SqlBuilderInterface {
        return new SimpleSqlBuilder($this);
    }
}