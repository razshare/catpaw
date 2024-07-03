<?php
namespace CatPaw\Web;

use Amp\Http\Server\Request;
use CatPaw\Ast\AstDetector;

class FilterItem {
    public static function create(
        string $after,
        string $name,
        string $bindName,
        string $operator,
        mixed $value,
    ):self {
        return new self(
            after: $after,
            name: $name,
            bindName: $bindName,
            operator: $operator,
            value: $value,
        );
    }
    private function __construct(
        public string $after,
        public string $name,
        public string $bindName,
        public string $operator,
        public mixed $value,
    ) {
    }
}

readonly class QueryItem {
    public static function create(
        string $query,
        string $after,
    ):self {
        return new self(
            query: $query,
            after: $after,
        );
    }
    private function __construct(
        public string $query,
        public string $after,
    ) {
    }
}

class Filter {
    /** @var array<FilterItem> */
    private array $items = [];
    /** @var false|(callable(FilterItem):string)*/
    private mixed $converter = false;

    public static function createFromRequest(Request $request):self {
        $filter = new self($request);
        return $filter->parse();
    }

    private function __construct(
        private readonly Request $request,
    ) {
    }
    
    private function parse():self {
        $detector = AstDetector::fromSource($this->request->getUri()->getQuery());

        /** @var array<QueryItem> */
        $queryItems = [];
        
        while (true) {
            $result = $detector->nextIgnoreQuotes('&', '%7C');

            if (!$result) {
                if ('' !== $detector->source) {
                    $queryItems[] = QueryItem::create($detector->source, '');
                }
                break;
            }

            if ('&' === $result->token || '%7C' === $result->token) {
                $queryItems[] = QueryItem::create(query: $result->before, after:'&&');
                continue;
            } else if ('%7C' === $result->token) {
                $queryItems[] = QueryItem::create(query: $result->before, after:'||');
                continue;
            }
        }

        /** @var array<string,int> */
        $counters = [];

        foreach ($queryItems as $queryItem) {
            $query = urldecode($queryItem->query);
            if (preg_match('/^([A-z_]\w*)=(like:|=|>|<|<=|>=|~|%)?(.*)$/', $query, $matches)) {
                if (!in_array($matches[2], ['','=','>','<','!','<=','>=','~','like:'])) {
                    continue;
                }

                $operator = $matches[2] ?? '';
                if ('' === $operator) {
                    $operator = '=';
                } else if ('!' === $operator) {
                    $operator = '!=';
                }

                $this->items[] = $item = FilterItem::create(
                    after: $queryItem->after,
                    name: $matches[1]     ?? '',
                    bindName: $matches[1] ?? '',
                    operator: $operator,
                    value: $matches[3] ?? '',
                );
                if (isset($counters[$item->name])) {
                    $counter = ++$counters[$item->name];
                    $item->bindName .= $counter;
                } else {
                    $counters[$item->name] = 0;
                }
            }
        }

        return $this;
    }

    
    /**
     * @param  (callable(FilterItem):string) $converter
     * @return Filter
     */
    public function withConverter(callable $converter):self {
        $this->converter = $converter;
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function properties() {
        /** @var array<string, mixed> */
        $properties = [];
        foreach ($this->items as $item) {// These are reserved for pagination.
            if ('start' === $item->name || 'size' === $item->name) {
                continue;
            }
            $properties[$item->bindName] = $item->value;
        }
        return $properties;
    }

    /**
     * 
     * @param  string $separator
     * @return string
     */
    public function join(string $separator = ' '):string {
        if (!$this->converter) {
            $this->converter = static function(FilterItem $item):string {
                $glue = match ($item->after) {
                    '&&'    => ' and',
                    '||'    => ' or',
                    default => '',
                };
                $operator = match ($item->operator) {
                    '~','like:' => 'like',
                    default => $item->operator,
                };
                return "{$item->name} $operator :{$item->bindName}$glue";
            };
        }

        /** @var array<string> */
        $chunks = [];
        foreach ($this->items as $item) {
            // These are reserved for pagination.
            if ('start' === $item->name || 'size' === $item->name) {
                continue;
            }
            
            $chunks[] = ($this->converter)($item);
        }
        return join($separator, $chunks);
    }
}