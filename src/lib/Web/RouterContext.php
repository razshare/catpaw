<?php
namespace CatPaw\Web;

class RouterContext {
    /**
     * @param array<string,array<string,Route>> $routes,
     */
    public function __construct(private array $routes = []) {
    }

    /**
     * @return array<string,array<string,Route>>
     */
    public function routes():array {
        return $this->routes;
    }

    public function addRoute(
        string $symbolicMethod,
        string $symbolicPath,
        Route $route,
    ):void {
        $this->routes[$symbolicMethod][$symbolicPath] = $route;
    }
}
