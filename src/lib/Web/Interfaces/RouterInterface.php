<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Web\Route;
use CatPaw\Web\RouterContext;
use Closure;
use ReflectionMethod;

interface RouterInterface {
    /**
     * Get the context of the router.
     * @return RouterContext
     */
    public function getContext():RouterContext;

    /**
     * Initialize a new route.
     * @param  string                  $symbolicMethod
     * @param  string                  $symbolicPath
     * @param  callable|Closure        $function
     * @param  string                  $workDirectory
     * @return Result<RouterInterface>
     */
    public function initialize(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $function,
        string $workDirectory = '',
    ):Result;

    /**
     * @param  ReflectionMethod     $reflectionMethod
     * @return array{string,string}
     */
    public function mappedParameters(ReflectionMethod $reflectionMethod):array;

    /**
     * Find a route.
     * @param  string      $symbolicMethod
     * @param  string      $symbolicPath
     * @return false|Route
     */
    public function findRoute(string $symbolicMethod, string $symbolicPath):false|Route;

    /**
     * Check if a route exists.
     * @param  string $symbolicMethod
     * @param  string $symbolicPath
     * @return bool
     */
    public function routeExists(string $symbolicMethod, string $symbolicPath):bool;

    /**
     * Find routes of a method.
     * @param  string       $symbolicMethod
     * @return array<Route>
     */
    public function findRoutesByMethod(string $symbolicMethod):array;

    /**
     * Define an alias for an already existing web server path name.
     * @param  string       $originalSymbolicMethod http method of the 2 parameters.
     * @param  string       $originalSymbolicPath   path name to capture.
     * @param  string       $aliasSymbolicPath      alias path name.
     * @return Result<None>
     */
    public function alias(
        string $originalSymbolicMethod,
        string $originalSymbolicPath,
        string $aliasSymbolicPath,
    ):Result;


    /**
     * Define an event callback for a custom http method.
     * @param  string                  $method   the name of the http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function custom(string $method, string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "COPY" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function copy(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "DELETE" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function delete(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "GET" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function get(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "HEAD" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function head(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "LINK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function link(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "LOCK" http method.
     *
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function lock(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function options(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "PATCH" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function patch(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "POST" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function post(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function propfind(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "PURGE" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function purge(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "PUT" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function put(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unknown(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "UNLINK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unlink(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unlock(string $path, callable|Closure $function):Result;

    /**
     * Define an event callback for the "VIEW" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function view(string $path, callable|Closure $function):Result;
}