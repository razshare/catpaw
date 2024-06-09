<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Route;
use Closure;
use ReflectionMethod;

interface RouterInterface {
    /**
     * Initialize a new route.
     * @param  string           $symbolicMethod
     * @param  string           $symbolicPath
     * @param  callable|Closure $function
     * @param  string           $workDirectory
     * @return Unsafe<None>
     */
    public function initialize(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $function,
        string $workDirectory = '',
    ):Unsafe;

    /**
     * @param  ReflectionMethod     $reflection_method
     * @return array{string,string}
     */
    public function getMappedParameters(ReflectionMethod $reflection_method): array;

    /**
     * Find a route.
     * @param  string      $symbolicMethod
     * @param  string      $symbolicPath
     * @return false|Route
     */
    public function findRoute(string $symbolicMethod, string $symbolicPath): false|Route;

    /**
     * Check if a route exists.
     * @param  string $symbolicMethod
     * @param  string $symbolicPath
     * @return bool
     */
    public function routeExists(string $symbolicMethod, string $symbolicPath): bool;

    /**
     * Find routes of a method.
     * @param  string       $symbolicMethod
     * @return array<Route>
     */
    public function findRoutesByMethod(string $symbolicMethod): array;

    /**
     * Define an alias for an already existing web server path name.
     * @param  string       $originalSymbolicMethod http method of the 2 parameters.
     * @param  string       $originalSymbolicPath   path name to capture.
     * @param  string       $aliasSymbolicPath      alias path name.
     * @return Unsafe<None>
     */
    public function alias(
        string $originalSymbolicMethod,
        string $originalSymbolicPath,
        string $aliasSymbolicPath,
    ):Unsafe;


    /**
     * Define an event callback for a custom http method.
     * @param  string           $method   the name of the http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function custom(string $method, string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "COPY" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function copy(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "DELETE" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function delete(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "GET" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function get(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "HEAD" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function head(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "LINK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function link(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "LOCK" http method.
     *
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function lock(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function options(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "PATCH" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function patch(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "POST" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function post(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function propfind(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "PURGE" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function purge(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "PUT" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function put(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function unknown(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "UNLINK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function unlink(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function unlock(string $path, callable|Closure $function):Unsafe;

    /**
     * Define an event callback for the "VIEW" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<None>
     */
    public function view(string $path, callable|Closure $function):Unsafe;
}