<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Core\Signal;

interface ServerInterface {
    /**
     * Invoke a function when the server starts.
     * @param  callable(HttpServer):(void|Result<void>) $function the function to invoke, with the http server as parameter.
     * @return Result<None>
     */
    public function onStart(callable $function):Result;

    /**
     * Get the location of the static files the server is serving.
     * @return string
     */
    public function staticsLocation():string;

    /**
     * List of middlewares to execute.
     * @param array<Middleware> $middlewares
     */
    public function widthMiddlewares(array $middlewares):self;

    /**
     * Interface to bind to.\
     * For example `0.0.0.0:80`.\
     * The default interface is `127.0.0.1:8080`.
     * @param string $interface
     */
    public function withInterface(string $interface):self;

    public function withSecureInterface(string $secureInterface):self;


    /**
     * The prefix of the api.
     * @param string $apiPrefix
     */
    public function withApiPrefix(string $apiPrefix):self;

    /**
     * Where to serve the api from.\
     * This path should contain your `get.php`, `post.php` (etc) files.
     * @param string $apiLocation
     */
    public function withApiLocation(string $apiLocation):self;
    
    /**
     * Where to serve static files from.
     * @param string $staticsLocation
     */
    public function withStaticsLocation(string $staticsLocation):self;

    /**
     * Enable compression.
     */
    public function withCompression():self;

    /**
     * The maximum number of connections to serve at the same time.
     * @param  int             $connectionLimit
     * @return ServerInterface
     */
    public function withConnectionLimit(int $connectionLimit):self;

    /**
     * The maximum number of connections to serve at the same time for a given IP.
     * @param  int             $connectionLimitPerIp
     * @return ServerInterface
     */
    public function withConnectionLimitPerIp(int $connectionLimitPerIp):self;

    /**
     * The maximum number of requests to queue to the event loop at the same time.
     * @param  int             $concurrencyLimit
     * @return ServerInterface
     */
    public function withConcurrencyLimit(int $concurrencyLimit):self;

    /**
     * @param array<string> $allowedMethods
     */
    public function withAllowedMethods(array $allowedMethods):self;
    
    /**
     * Start the server.
     *
     * This method will resolve when `::stop` is invoked or one of the following signals is sent to the program `SIGHUP`, `SIGINT`, `SIGQUIT`, `SIGTERM`.
     * @param  false|Signal $ready This signal triggers whenever the server is ready to serve requests.
     * @return Result<None>
     */
    public function start(false|Signal $ready = false):Result ;


    /**
     * Stop the server.
     * @return Result<None>
     */
    public function stop():Result;
}