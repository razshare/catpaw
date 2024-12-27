<?php
namespace CatPaw\Database\Implementations;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\error;
use CatPaw\Core\LinkedList;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Result;

use function CatPaw\Core\uuid;
use CatPaw\Database\Interfaces\DatabaseInterface;
use CatPaw\Database\Interfaces\SqlBuilderInterface;
use CatPaw\Web\Page;
use Error;
use Throwable;

#[Provider(singleton:false)]
class SimpleSqlBuilder implements SqlBuilderInterface {
    private string $content = '';
    /** @var LinkedList<Error> */
    private LinkedList $errors;
    /** @var array<string,mixed> */
    private array $parameters = [];

    public function __construct(private DatabaseInterface $database) {
        $this->errors = new LinkedList;
    }

    public function limit(int $offset, int $count = 10): SqlBuilderInterface {
        $this->content .= "limit $offset, $count ";
        return $this;
    }

    public function page(Page $page): SqlBuilderInterface {
        $this->content .= "limit {$page->start}, {$page->size} ";
        return $this;
    }

    /**
     * Build the instruction.
     * @return Result<string>
     */
    private function build(): Result {
        if ($this->errors->count() > 0) {
            $message = '';
            for ($this->errors->rewind();$this->errors->valid();$this->errors->next()) {
                $error = $this->errors->current();
                $message .= $error->getMessage().PHP_EOL;
            }
            return error($message);
        }

        return ok($this->content);
    }

    /**
     * @template T
     * @param  class-string<T> $className
     * @return Result<false|T>
     */
    public function one(string $className):Result {
        $instruction = $this->build()->unwrap($error);
        if ($error) {
            return error($error);
        }
        $response = $this->database->send($instruction, $this->parameters)->unwrap($error);
        if ($error) {
            return error($error);
        }

        if (!isset($response[0])) {
            // @phpstan-ignore return.type
            return ok(false);
        }
        
        try {
            $item = new $className();
            
            foreach ($response[0] as $key => $value) {
                $item->$key = $value;
            }

            // @phpstan-ignore return.type
            return ok($item);
        } catch (Throwable $error) {
            return error($error);
        }
    }


    /**
     * @template T
     * @param  class-string<T>  $className
     * @return Result<array<T>>
     */
    public function many(string $className):Result {
        $instruction = $this->build()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $response = $this->database->send($instruction, $this->parameters)->unwrap($error);
        if ($error) {
            return error($error);
        }
        
        try {
            /** @var array<T> */
            $items = [];

            foreach ($response as $value) {
                $item = new $className();
            
                foreach ($response[0] as $key => $value) {
                    $item->$key = $value;
                }

                $items[] = $item;
            }

            // @phpstan-ignore return.type
            return ok($items);
        } catch (Throwable $error) {
            return error($error);
        }
    }


    /**
     * @return Result<None>
     */
    public function none():Result {
        $instruction = $this->build()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $this->database->send($instruction, $this->parameters)->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok();
    }

    public function select(string ...$domain):self {
        if (0 === count($domain)) {
            $domain = '*';
        } else {
            $domain = join(',', $domain);
        }

        $this->content .= "select $domain ";
        return $this;
    }

    public function from(string $table):self {
        $this->content .= "from $table ";
        return $this;
    }

    public function insert():self {
        $this->content .= 'insert ';
        return $this;
    }

    public function into(string $into, string ...$domain):self {
        if (0 === count($domain)) {
            $domain = '';
        } else {
            $domain = '('.join(',', $domain).')';
        }
        $this->content .= "into $into $domain ";
        return $this;
    }

    public function value():self {
        $this->content .= 'value ';
        return $this;
    }

    public function values():self {
        $this->content .= 'values ';
        return $this;
    }

    public function update(string $table):self {
        $this->content .= "update $table ";
        return $this;
    }

    public function set(array|object $items):self {
        $id = uuid();
        if (!is_array($items)) {
            $items = (array)$items;
        }

        $this->content .= 'set ';
        
        $index = 0;
        foreach ($items as $key => $value) {
            $pkey = "{$key}_{$id}";
            if (0 !== $index) {
                $this->content .= ", ";
            }
            $this->parameters[$pkey] = $value;
            $this->content .= "$key = :$pkey";
            $index++;
        }

        $this->content .= ' ';

        return $this;
    }

    public function not(bool $literal = true):self {
        $this->content .= match ($literal) {
            true  => 'not ',
            false => '!'
        };
        return $this;
    }

    public function equals():self {
        $this->content .= '= ';
        return $this;
    }

    public function notEquals():self {
        $this->content .= '!= ';
        return $this;
    }

    public function greaterThan():self {
        $this->content .= '> ';
        return $this;
    }
    
    public function lesserThan():self {
        $this->content .= '< ';
        return $this;
    }
    
    public function greaterThanOrEquals():self {
        $this->content .= '>= ';
        return $this;
    }
    
    public function lesserThanOrEquals():self {
        $this->content .= '<= ';
        return $this;
    }
    
    public function like():self {
        $this->content .= 'like ';
        return $this;
    }

    public function name(string $name):self {
        $this->content .= "$name ";
        return $this;
    }

    public function parameter(string $name, mixed $value):self {
        $this->parameters[$name] = $value;
        $this->content .= ":$name ";
        return $this;
    }

    public function where():self {
        $this->content .= 'where ';
        return $this;
    }

    public function between():self {
        $this->content .= 'between ';
        return $this;
    }

    public function and():self {
        $this->content .= 'and ';
        return $this;
    }

    public function or():self {
        $this->content .= 'or ';
        return $this;
    }

    public function in():self {
        $this->content .= 'in ';
        return $this;
    }

    public function having():self {
        $this->content .= 'having ';
        return $this;
    }

    public function group():self {
        $this->content .= 'group ';
        return $this;
    }

    public function by():self {
        $this->content .= 'by ';
        return $this;
    }
}