<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Web\Route;
use CatPaw\Web\RouterContext;
use Closure;

interface RouterInterface {
    /**
     * Get the context of the router.
     * @return RouterContext
     */
    public function context():RouterContext;

    /**
     * Get the routes.
     * @return array<string,array<string,Route>>
     */
    public function routes():array;

    /**
     * Add a new route handler.
     * @param  string                  $symbolicMethod
     * @param  string                  $symbolicPath
     * @param  callable|Closure        $function
     * @param  string                  $workDirectory
     * @return Result<RouterInterface>
     */
    public function addHandler(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $function,
        string $workDirectory = '',
    ):Result;

    /**
     * Add a new route handler alias.
     * @param  string       $symbolicMethod    original method.
     * @param  string       $symbolicPath      original path.
     * @param  string       $aliasSymbolicPath alias path.
     * @return Result<None>
     */
    public function addHandlerAlias(
        string $symbolicMethod,
        string $symbolicPath,
        string $aliasSymbolicPath,
    ):Result;

    /**
     * Add a new route controller.
     * @param  string       $symbolicPath path of the route.
     * @param  class-string $className    name of the class that is being promoted to controller.
     * @return Result<None>
     */
    public function addController(string $symbolicPath, string $className):Result;
}