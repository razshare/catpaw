<?php

namespace CatPaw\Web;

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use function Amp\Http\Server\Middleware\stackMiddleware;
use Amp\Http\Server\SocketHttpServer;
use CatPaw\Core\Bootstrap;
use CatPaw\Core\Container;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Signal;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Interfaces\FileServerInterface;
use CatPaw\Web\Interfaces\SessionInterface;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;

class Server {
    private static Server $singleton;
    /** @var array<callable(HttpServer):(void|Unsafe<void>)> */
    private static array $onStartListeners = [];

    /**
     * Invoke a function when the server starts.
     * @param  callable(HttpServer):(void|Unsafe<void>) $function the function to invoke, with the http server as parameter.
     * @return Unsafe<None>
     */
    public static function onStart(callable $function):Unsafe {
        self::$onStartListeners[] = $function;
        if (isset(self::$singleton) && isset(self::$singleton->httpServer) && self::$singleton->httpServerStarted) {
            $result = $function(self::$singleton->httpServer);
            if ($result instanceof Unsafe) {
                $result->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        }
        return ok();
    }

    /**
     *  Get the server singleton.
     * @return Server
     */
    public static function get(): Server {
        if (isset(self::$singleton)) {
            return self::$singleton;
        }


        

        return self::$singleton = new self(router           : Router::create());
    }

    private SocketHttpServer $httpServer;
    private RouteResolver $resolver;
    private FileServerInterface $fileServer;
    private bool $httpServerStarted = false;

    /** @var array<Middleware> */
    private array $middlewares = [];
    private string $interface  = '127.0.0.1:8080';
    // @phpstan-ignore-next-line
    private string $secureInterface   = '';
    private string $apiPrefix         = '/';
    private string $apiLocation       = '';
    private string $staticsLocation   = '';
    private bool $compression         = false;
    private int $connectionLimit      = 1000;
    private int $connectionLimitPerIp = 10;
    private int $concurrencyLimit     = 1000;
    /** @var array<string> */
    private array $allowedMethods = [];

    /**
     * @param  Router $router
     * @return void
     */
    private function __construct(public readonly Router $router) {
    }

    public function getStaticsLocation():string {
        return $this->staticsLocation;
    }

    /**
     * List of middlewares to execute.
     * @param  array<Middleware> $middlewares
     * @return Server
     */
    public function widthMiddlewares(array $middlewares):self {
        $this->middlewares = $middlewares;
        return $this;
    }
    
    /**
     * Interface to bind to.\
     * For example `0.0.0.0:80`.\
     * The default interface is `127.0.0.1:8080`.
     * @param  string $interface
     * @return Server
     */
    public function withInterface(string $interface):self {
        $this->interface = $interface;
        return $this;
    }
    
    public function withSecureInterface(string $secureInterface):self {
        $this->secureInterface = $secureInterface;
        return $this;
    }

    /**
     * The prefix of the api.
     * @param  string $apiPrefix
     * @return Server
     */
    public function withApiPrefix(string $apiPrefix):self {
        if (!str_starts_with($apiPrefix, '/')) {
            $apiPrefix = "/$apiPrefix";
        }
        $this->apiPrefix = $apiPrefix;
        return $this;
    }

    /**
     * Where to serve the api from.\
     * This path should contain your `get.php`, `post.php` (etc) files.
     * @param  string $apiLocation
     * @return Server
     */
    public function withApiLocation(string $apiLocation):self {
        $this->apiLocation = $apiLocation;
        return $this;
    }

    /**
     * Where to serve static files from.
     * @param  string $staticsLocation
     * @return Server
     */
    public function withStaticsLocation(string $staticsLocation):self {
        $this->staticsLocation = $staticsLocation;
        return $this;
    }

    /**
     * Enables compression.
     * @return Server
     */
    public function withCompression():self {
        $this->compression = true;
        return $this;
    }

    public function withConnectionLimit(int $connectionLimit):self {
        $this->connectionLimit = $connectionLimit;
        return $this;
    }

    public function withConnectionLimitPerIp(int $connectionLimitPerIp):self {
        $this->connectionLimitPerIp = $connectionLimitPerIp;
        return $this;
    }

    public function withConcurrencyLimit(int $concurrencyLimit):self {
        $this->concurrencyLimit = $concurrencyLimit;
        return $this;
    }

    /**
     * 
     * @param  array<string> $allowedMethods
     * @return Server
     */
    public function withAllowedMethods(array $allowedMethods):self {
        $this->allowedMethods = $allowedMethods;
        return $this;
    }

    public function middleware(Middleware $middleware): void {
        $this->middlewares[] = $middleware;
    }

    public function setFileServer(FileServerInterface $fileServer):self {
        $this->fileServer = $fileServer;
        return $this;
    }

    /**
     * Start the server.
     *
     * This method will resolve when `::stop` is invoked or one of the following signals is sent to the program `SIGHUP`, `SIGINT`, `SIGQUIT`, `SIGTERM`.
     * @param  false|Signal $ready the server will trigger this signal whenever it's ready to serve requests.
     * @return Unsafe<None>
     */
    public function start(false|Signal $ready = false):Unsafe {
        $logger = Container::get(LoggerInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }

        if (!Container::isProvided(SessionInterface::class)) {
            Container::provide(SessionInterface::class, SessionWithMemory::create(...));
        }

        self::initializeRoutes(
            logger: $logger,
            router: $this->router,
            apiPrefix: $this->apiPrefix,
            apiLocation: $this->apiLocation,
        )->unwrap($error);

        if ($error) {
            $logger->error((string)$error);
        }

        Bootstrap::onKill(function() {
            $this->stop();
        });

        $this->resolver = RouteResolver::create($this);

        if (isset($this->httpServer)) {
            if ($this->httpServerStarted) {
                return error("Server already started.");
            }
            return error("Server already created.");
        }
        $endSignal = new DeferredFuture;
        try {
            if (!isset($this->fileServer)) {
                $fileServer = FileServer::create($this)->unwrap($error);
                if ($error) {
                    return error($error);
                }
                $this->fileServer = $fileServer;
            }

            $stopper = function(string $callbackId) {
                EventLoop::cancel($callbackId);
                Bootstrap::kill();
            };

            EventLoop::onSignal(SIGHUP, $stopper);
            EventLoop::onSignal(SIGINT, $stopper);
            EventLoop::onSignal(SIGQUIT, $stopper);
            EventLoop::onSignal(SIGTERM, $stopper);

            $requestHandler   = ServerRequestHandler::create($logger, $this->fileServer, $this->resolver);
            $stackedHandler   = stackMiddleware($requestHandler, ...$this->middlewares);
            $errorHandler     = ServerErrorHandler::create($logger);
            $this->httpServer = SocketHttpServer::createForDirectAccess(
                logger: $logger,
                enableCompression: $this->compression,
                connectionLimit: $this->connectionLimit,
                connectionLimitPerIp: $this->connectionLimitPerIp,
                concurrencyLimit: $this->concurrencyLimit,
                allowedMethods: $this->allowedMethods?:null,
            );

            $this->httpServer->onStop(static function() use ($endSignal) {
                if (!$endSignal->isComplete()) {
                    $endSignal->complete();
                }
            });
            
            $this->httpServer->expose($this->interface);

            $this->httpServer->start($stackedHandler, $errorHandler);
            $this->httpServerStarted = true;
            if ($ready) {
                $ready->send();
            }

            foreach (self::$onStartListeners as $function) {
                $result = $function($this->httpServer);
                if ($result instanceof Unsafe) {
                    $result->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                }
            }

            $endSignal->getFuture()->await();
            return ok();
        } catch (Throwable $e) {
            if (!$endSignal->isComplete()) {
                $endSignal->complete();
            }
            return error($e);
        }
    }

    /**
     * Stop the server.
     * @return Unsafe<None>
     */
    public function stop(): Unsafe {
        if (isset($this->httpServer)) {
            try {
                $this->httpServer->stop();
                return ok();
            } catch(CompositeException $e) {
                return error($e);
            }
        }
        return ok();
    }

    /**
     * ù
     * @param  LoggerInterface $logger
     * @param  Router          $router
     * @param  string          $apiPrefix
     * @param  string          $apiLocation
     * @return Unsafe<None>
     */
    private static function initializeRoutes(
        LoggerInterface $logger,
        Router $router,
        string $apiPrefix,
        string $apiLocation,
    ): Unsafe {
        if ($apiLocation) {
            $flatList = Directory::flat($apiLocation)->unwrap($error);
            if ($error) {
                return error($error);
            }

            foreach ($flatList as $fileName) {
                if (!str_ends_with(strtolower($fileName), '.php')) {
                    continue;
                }
                $offset       = strpos($fileName, $apiLocation);
                $offset       = $offset?:0;
                $relativePath = substr($fileName, $offset + strlen($apiLocation));

                if (!str_starts_with($relativePath, '.'.DIRECTORY_SEPARATOR)) {
                    if ($handler = require_once $fileName) {
                        $fileName = preg_replace('/\.php$/i', '', preg_replace('/\.\/+/', '/', '.'.DIRECTORY_SEPARATOR.$relativePath));

                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            $fileName = preg_replace('/\\\\/', '/', $fileName);
                        }

                        if (!preg_match('/^(.*)(\.|\/)(.*)$/', $fileName, $matches)) {
                            $logger->error("Invalid api path for $fileName.", ["matches" => $matches]);
                            continue;
                        }

                        $symbolicPath   = $apiPrefix.$matches[1];
                        $symbolicPath   = preg_replace(['/^\/+/','/\/index$/'], ['/',''], $symbolicPath);
                        $symbolicMethod = strtoupper($matches[3] ?? 'get');

                        $routeExists = $router->routeExists($symbolicMethod, $symbolicPath);

                        if (!$routeExists) {
                            $cwd = dirname($apiLocation.$fileName)?:'';
                            $router->initialize($symbolicMethod, $symbolicPath, $handler, $cwd)->unwrap($error);
                            if ($error) {
                                return error($error);
                            }
                        } else {
                            $logger->info("Route `$symbolicMethod $symbolicPath` already exists. Will not overwrite.");
                        }
                    }
                }
            }
        }
        return ok();
    }
}
