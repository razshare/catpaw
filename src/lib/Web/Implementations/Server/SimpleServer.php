<?php
namespace CatPaw\Web\Implementations\Server;

use Amp\CompositeException;
use Amp\DeferredFuture;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use function Amp\Http\Server\Middleware\stackMiddleware;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\SocketHttpServer;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Bootstrap;
use CatPaw\Core\Container;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Signal;
use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Document\MountContext;
use CatPaw\Web\Interfaces\RouteResolverInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\ServerErrorHandler;
use CatPaw\Web\SessionWithMemory;
use CatPaw\Web\Symbolics;
use Closure;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;

#[Provider]
class SimpleServer implements ServerInterface {
    private SocketHttpServer $httpServer;
    private bool $httpServerStarted = false;

    /** @var array<Middleware> */
    private array $middlewares = [];
    private string $interface  = '127.0.0.1:8080';
    // @phpstan-ignore-next-line
    private string $secureInterface   = '';
    private string $apiPrefix         = '/';
    private string $apiLocation       = '';
    private string $documentsPrefix   = '/';
    private string $documentsLocation = '';
    private string $staticsLocation   = '';
    private bool $compression         = false;
    private int $connectionLimit      = 1000;
    private int $connectionLimitPerIp = 10;
    private int $concurrencyLimit     = 1000;
    /** @var array<string> */
    private array $allowedMethods = [];

    /**
     * @param  RouterInterface $router
     * @return void
     */
    public function __construct(
        public readonly RouterInterface $router,
        public readonly LoggerInterface $logger,
        public readonly RouteResolverInterface $routeResolver,
        public readonly RequestHandler $requestHandler,
    ) {
    }

    

    /** @var array<callable(HttpServer):(void|Result<void>)> */
    private array $onStartListeners = [];

    /**
     * Invoke a function when the server starts.
     * @param  callable(HttpServer):(void|Result<void>) $function the function to invoke, with the http server as parameter.
     * @return Result<None>
     */
    public function onStart(callable $function):Result {
        $this->onStartListeners[] = $function;
        if (isset($this->httpServer) && $this->httpServerStarted) {
            $result = $function($this->httpServer);
            if ($result instanceof Result) {
                $result->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        }
        return ok();
    }

    public function staticsLocation():string {
        return $this->staticsLocation;
    }

    /**
     * List of middlewares to execute.
     * @param array<Middleware> $middlewares
     */
    public function widthMiddlewares(array $middlewares):self {
        $this->middlewares = $middlewares;
        return $this;
    }
    
    /**
     * Interface to bind to.\
     * For example `0.0.0.0:80`.\
     * The default interface is `127.0.0.1:8080`.
     * @param string $interface
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
     * @param string $apiPrefix
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
     * @param string $apiLocation
     */
    public function withApiLocation(string $apiLocation):self {
        $this->apiLocation = $apiLocation;
        return $this;
    }

    public function withDocumentsPrefix(string $documentsPrefix):self {
        $this->documentsPrefix = $documentsPrefix;
        return $this;
    }

    public function withDocumentsLocation(string $documentsLocation):self {
        $this->documentsLocation = $documentsLocation;
        return $this;
    }

    /**
     * Where to serve static files from.
     * @param string $staticsLocation
     */
    public function withStaticsLocation(string $staticsLocation):self {
        $this->staticsLocation = $staticsLocation;
        return $this;
    }

    /**
     * Enables compression.
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
     * @param array<string> $allowedMethods
     */
    public function withAllowedMethods(array $allowedMethods):self {
        $this->allowedMethods = $allowedMethods;
        return $this;
    }

    /**
     * Start the server.
     *
     * This method will resolve when `::stop` is invoked or one of the following signals is sent to the program `SIGHUP`, `SIGINT`, `SIGQUIT`, `SIGTERM`.
     * @param  false|Signal $ready the server will trigger this signal whenever it's ready to serve requests.
     * @return Result<None>
     */
    public function start(false|Signal $ready = false):Result {
        $logger = Container::get(LoggerInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }

        if (!Container::isProvided(SessionInterface::class)) {
            Container::provide(SessionInterface::class, SessionWithMemory::create(...));
        }

        $this->initializeRoutes(
            apiPrefix: $this->apiPrefix,
            apiLocation: $this->apiLocation,
        )->unwrap($error);

        if ($error) {
            return error($error);
        }

        $this->initializeDocuments(
            documentsPrefix: $this->documentsPrefix,
            documentsLocation: $this->documentsLocation,
        )->unwrap($error);

        if ($error) {
            return error($error);
        }

        Bootstrap::onKill(function() {
            $this->stop();
        });

        if (isset($this->httpServer)) {
            if ($this->httpServerStarted) {
                return error("Server already started.");
            }
            return error("Server already created.");
        }
        $endSignal = new DeferredFuture;
        try {
            $stopper = function(string $callbackId) {
                EventLoop::cancel($callbackId);
                Bootstrap::kill();
            };

            $sighupId  = EventLoop::onSignal(SIGHUP, $stopper);
            $sigintId  = EventLoop::onSignal(SIGINT, $stopper);
            $sigquitId = EventLoop::onSignal(SIGQUIT, $stopper);
            $sigtermId = EventLoop::onSignal(SIGTERM, $stopper);

            $stackedHandler   = stackMiddleware($this->requestHandler, ...$this->middlewares);
            $errorHandler     = ServerErrorHandler::create($logger);
            $this->httpServer = SocketHttpServer::createForDirectAccess(
                logger: $this->logger,
                enableCompression: $this->compression,
                connectionLimit: $this->connectionLimit,
                connectionLimitPerIp: $this->connectionLimitPerIp,
                concurrencyLimit: $this->concurrencyLimit,
                allowedMethods: $this->allowedMethods?:null,
            );

            $this->httpServer->onStop(static function() use (
                $endSignal,
                $sighupId,
                $sigintId,
                $sigquitId,
                $sigtermId,
            ) {
                EventLoop::cancel($sighupId);
                EventLoop::cancel($sigintId);
                EventLoop::cancel($sigquitId);
                EventLoop::cancel($sigtermId);
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

            foreach ($this->onStartListeners as $function) {
                $result = $function($this->httpServer);
                if ($result instanceof Result) {
                    $result->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                }
            }

            $endSignal->getFuture()->await();
            return ok();
        } catch (Throwable $error) {
            if (!$endSignal->isComplete()) {
                $endSignal->complete();
            }
            return error($error);
        }
    }

    /**
     * Stop the server.
     * @return Result<None>
     */
    public function stop():Result {
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

    // /**
    //  * 
    //  * @param  string       $documentsPrefix
    //  * @param  string       $documentsLocation
    //  * @return Result<None>
    //  */
    // private function initializeDocuments(string $documentsPrefix, string $documentsLocation):Result {
    //     $document = Container::get(DocumentInterface::class)->unwrap($error);
    //     if ($error) {
    //         return error($error);
    //     }

    //     if ($documentsLocation) {
    //         if (!str_starts_with($documentsLocation, '/')) {
    //             $dir               = getcwd();
    //             $documentsLocation = (string)asFileName($dir, $documentsLocation);
    //         }

    //         $flatList = Directory::flat($documentsLocation)->unwrap($error);
    //         if ($error) {
    //             return error($error);
    //         }

    //         foreach ($flatList as $fileName) {
    //             $document->mount(
    //                 fileName: $fileName,
    //                 onLoad: fn (MountContext $context) 
    //                         => $this->initializeRoutesFromDocumentMountContext(
    //                             documentsPrefix: $documentsPrefix,
    //                             documentsLocation: $documentsLocation,
    //                             context: $context,
    //                         )
    //             )->unwrap($error);
    //             if ($error) {
    //                 return error($error);
    //             }
    //         }
    //     }


    //     return ok();
    // }

    /**
     * 
     * @param  string       $documentsPrefix
     * @param  string       $documentsLocation
     * @return Result<None>
     */
    private function initializeDocuments(
        string $documentsPrefix,
        string $documentsLocation,
    ):Result {
        if (!$documentsLocation) {
            return ok();
        }

        if (!str_starts_with($documentsLocation, '/')) {
            $dir               = getcwd();
            $documentsLocation = (string)asFileName($dir, $documentsLocation);
        }

        $document = Container::get(DocumentInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }
        
        $flatList = Directory::flat($documentsLocation)->unwrap($error);
        if ($error) {
            return error($error);
        }

        foreach ($flatList as $fileName) {
            if (!str_ends_with(strtolower($fileName), '.php')) {
                continue;
            }
                
            if (str_starts_with($fileName, '.'.DIRECTORY_SEPARATOR)) {
                return error("Unexpected relative file name `$fileName` while initializing documents.");
            }

            $symbolics = Symbolics::fromRootAndPrefixAndFileName(
                prefix: $documentsPrefix,
                root: $documentsLocation,
                fileName: $fileName,
            )->unwrap($error);
            if ($error) {
                return error($error);
            }

            $mountAttempt = $document->mount($fileName, function(MountContext $mountContext) use (
                $documentsLocation,
                $fileName,
                $symbolics,
            ) {
                // Cwd.
                $cwd = dirname($documentsLocation.$fileName)?:'';

                // Functions.
                foreach ($mountContext->functions as $functionName => $function) {
                    $this->router
                        ->initialize($functionName, $symbolics->path, $function, $mountContext, $cwd)
                        ->unwrap($error);

                    if ($error) {
                        return error($error);
                    }
                }

                // Variable functions.
                foreach ($mountContext->variables as $variableName => $variable) {
                    if (!($variable instanceof Closure)) {
                        continue;
                    }

                    $this->router
                        ->initialize($variableName, $symbolics->path, $variable, $mountContext, $cwd)
                        ->unwrap($error);

                    if ($error) {
                        return error($error);
                    }
                }

                return ok();
            });

            $mountAttempt->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        return ok();
    }

    /**
     * @param  string       $apiPrefix
     * @param  string       $apiLocation
     * @return Result<None>
     */
    private function initializeRoutes(string $apiPrefix, string $apiLocation):Result {
        if (!$apiLocation) {
            return ok();
        }

        if (!str_starts_with($apiLocation, '/')) {
            $dir         = getcwd();
            $apiLocation = (string)asFileName($dir, $apiLocation);
        }

        $flatList = Directory::flat($apiLocation)->unwrap($error);
        if ($error) {
            return error($error);
        }

        foreach ($flatList as $fileName) {
            if (!str_ends_with(strtolower($fileName), '.php')) {
                continue;
            }
                
            if (str_starts_with($fileName, '.'.DIRECTORY_SEPARATOR)) {
                return error("Unexpected relative file name `$fileName` while initializing routes.");
            }

            if ($handler = require_once $fileName) {
                if (!is_callable($handler)) {
                    return error("File `$fileName` is a php file that lives under a filesystem router directory, hence it must return a callable function, but it doesn't. If this file is not needed, please consider deleting it or moving it to a different directory.");
                }

                $symbolics = Symbolics::fromRootAndPrefixAndFileName(
                    prefix: $apiPrefix,
                    root: $apiLocation,
                    fileName: $fileName,
                )->unwrap($error);

                if ($error) {
                    return error($error);
                }

                $routeExists = $this->router->routeExists($symbolics->path, $symbolics->path);

                if ($routeExists) {
                    return error("Route `$symbolics->method $symbolics->path` already exists. Will not overwrite.");
                }

                $cwd = dirname($apiLocation.$fileName)?:'';
                $this->router
                    ->initialize($symbolics->method, $symbolics->path, $handler, false, $cwd)
                    ->unwrap($error);

                if ($error) {
                    return error($error);
                }
            }
        }
        
        return ok();
    }
}
