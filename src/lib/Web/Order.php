<?php
namespace CatPaw\Web;

use Stringable;

readonly class Order implements Stringable {
    /**
     * 
     * @param  array       $items
     * @return false|Order
     */
    public static function fromArrayKeys(array $items):false|self {
        return self::fromFirstValidString(...array_keys($items));
    }

    /**
     * 
     * @param  string[]    $items
     * @return false|Order
     */
    public static function fromFirstValidString(string ...$items):false|self {
        foreach ($items as $value) {
            if ($page = self::fromString($value)) {
                return $page;
            }
        }
        return false;
    }

    /**
     * 
     * @param  string      $items
     * @return false|Order
     */
    public static function fromString(string $items):false|self {
        $direction = 'asc';
        if (
            preg_match('/(ASC|DESC):([A-z0-9,]+)/i', $items, $groups)
            && count($groups) >= 3
        ) {
            $direction = $groups[1] ?? $direction;
            $items     = explode(',', $groups[2]);
            return self::by(
                direction: $direction,
                items: $items,
            );
        } 
        
        return false;
    }

    /**
     * 
     * @param  array|string $items
     * @return Order
     */
    public static function of(array|string $items):self {
        return self::by(
            direction: 'asc',
            items: $items,
        );
    }

    /**
     * 
     * @param  string       $direction 'asc' or 'desc'
     * @param  array|string $items
     * @return self
     */
    private static function by(
        string $direction,
        array|string $items,
    ):self {
        return new self(
            direction: $direction,
            items: is_string($items)?explode(',', $items):$items,
        );
    }

    /**
     * 
     * @param  string $direction
     * @param  array  $items
     * @return void
     */
    private function __construct(
        private string $direction,
        private array $items,
    ) {
    }

    public function asc():self {
        return self::by(
            direction: "asc",
            items: $this->items,
        );
    }

    public function desc():self {
        return self::by(
            direction: "desc",
            items: $this->items,
        );
    }

    public function getItems():array {
        return $this->items;
    }

    public function getDirection():string {
        return $this->direction;
    }


    public function toQuery():string {
        return "$this->direction:".urlencode(join(',', $this->items));
    }

    public function __toString():string {
        $serializedItems = join(',', $this->items);
        return <<<SQL
            order by $serializedItems $this->direction
            SQL;
    }
}