<?php
namespace CatPaw\Web;

use CatPaw\Container;
use Psr\Log\LoggerInterface;


class RouterContext {
    /**
     * @param array<Route> $routes,
     */
    public static function create($routes = []):self {
        return new self(routes: $routes);
    }

    private LoggerInterface $logger;

    /**
     * @param array<RouteContext> $routes,
     */
    private function __construct(private array $routes) {
        /** @var Unsafe<LoggerInterface> */
        $loggerAttempt = Container::create(LoggerInterface::class);
        if (!$loggerAttempt->error) {
            $this->logger = $loggerAttempt->value;
        }
    }

    /**
     * @return array<Route>
     */
    public function findAllRoutes():array {
        return array_values(array_values($this->routes));
    }

    /**
     * @return array<Route>
     */
    public function findRoutesByMethod(string $method):array {
        return $this->routes[$method] ?? [];
    }

    /**
     * @return array<Route>
     */
    public function findRoutesByPath(string $path):array {
        $functions = [];
        foreach ($this->routes as $paths) {
            foreach ($paths as $pathLocal => $function) {
                if ($pathLocal !== $path) {
                    continue;
                }

                $functions[] = $function;
            }
        }
        return $functions;
    }

    public function routeExists(string $method, string $path):bool {
        return isset($this->routes[$method][$path]);
    }

    public function findRoute(string $method, string $path):Route {
        return $this->routes[$method][$path];
    }

    public function setRoute(
        string $symbolicMethod,
        string $symbolicPath,
        Route $route,
    ):void {
        if ($this->routes[$symbolicMethod][$symbolicPath] ?? false) {
            $this->logger->warning("Overwriting route handler $symbolicMethod $symbolicPath.");
        }
        
        $this->routes[$symbolicMethod][$symbolicPath] = $route;
    }
}