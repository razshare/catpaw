<?php
namespace CatPaw\Web;

use League\Uri\Components\URLSearchParams;
use Psr\Http\Message\UriInterface;

/**
 * A page that starts from `$start` and is `$size` long.\
 * 
 * > **Note**\
 * > When injected into a route handler, the page is initialized with the values of the query strings `start` and `size`.\
 * > For example a query string of `?start=3&size=3` will initialize a page with `Page::create(start:3, size:3)`.
 * @package CatPaw\Web
 */
class Page {
    public static function of(int $size):self {
        return new self(0, $size);
    }

    /**
     * 
     * @param  int  $start Start position of the page.\
     *                     If this value goes below 0 it will be overwritten to 0.
     * @param  int  $size  size of the page.
     * @return Page
     */
    public static function create(int $start, int $size):self {
        return new self($start, $size);
    }

    private string $query    = '';
    private string $scheme   = '';
    private string $hostname = '';
    private int $port        = 80;
    private string $path     = '';

    private function __construct(
        readonly public int $start,
        readonly public int $size,
    ) {
    }

    public function nextLink():string {
        $next   = $this->next();
        $search = new URLSearchParams($this->query);
        $search->set("start", $next->start);
        $search->set("size", $next->size);
        $query = $search->isEmpty()?'':"?$search";
        return "$this->scheme://$this->hostname:$this->port$this->path$query";
    }

    public function previousLink():string {
        $previous = $this->previous();
        $search   = new URLSearchParams($this->query);
        $search->set("start", $previous->start);
        $search->set("size", $previous->size);
        $query = $search->isEmpty()?'':"?$search";
        return "$this->scheme://$this->hostname:$this->port$this->path$query";
    }

    /**
     * Get the next page.
     * @return Page
     */
    public function next():self {
        return Page::create($this->start + $this->size, $this->size);
    }

    /**
     * Get the previous page.
     * @return Page
     */
    public function previous():self {
        if ($this->start - $this->size < 0) {
            return Page::create(0, $this->size);
        }
        return Page::create($this->start - $this->size, $this->size);
    }

    /**
     * Used to generate in-place links in the response, like `nextHref` and `previousHref`.
     * @param  UriInterface $uri
     * @return Page
     */
    public function withUri(UriInterface $uri):self {
        $this->query    = $uri->getQuery();
        $this->scheme   = $uri->getScheme();
        $this->hostname = $uri->getHost();
        $this->port     = $uri->getPort() ?? 80;
        $this->path     = $uri->getPath();
        return $this;
    }
}