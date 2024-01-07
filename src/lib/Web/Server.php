<?php
namespace CatPaw\Web;

use function Amp\async;
use Amp\DeferredFuture;

use function Amp\File\isDirectory;
use Amp\Future;
use Amp\Http\Server\Middleware;

use function Amp\Http\Server\Middleware\stackMiddleware;
use Amp\Http\Server\SocketHttpServer;
use CatPaw\Attributes\Option;

use CatPaw\Bootstrap;
use CatPaw\Container;
use CatPaw\Directory;
use function CatPaw\error;
use CatPaw\File;

use function CatPaw\isPhar;
use function CatPaw\ok;

use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Session;
use CatPaw\Web\Interfaces\FileServerInterface;
use Phar;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Throwable;

class Server {
    private static function findFirstValidWebDirectory(array $www):string {
        if (isPhar()) {
            $phar = \Phar::running();
            foreach ($www as $directory) {
                $directory = "$phar/$directory";
                if (File::exists($directory)) {
                    if (isDirectory($directory)) {
                        return $directory;
                    }
                }
            }
        } else {
            foreach ($www as $directory) {
                if (isDirectory($directory)) {
                    return $directory;
                }
            }
        }
        return '';
    }


    private static function findFirstValidRoutesDirectory(array $api) : string {
        foreach ($api as $directory) {
            if (File::exists($directory)) {
                if (isDirectory($directory)) {
                    return $directory;
                    break;
                }
            }

            $isPhar = isPhar();
            $phar   = \Phar::running();

            if ($isPhar) {
                $directory = "$phar/$directory";
                if (File::exists($directory)) {
                    if (isDirectory($directory)) {
                        return $directory;
                    }
                }
            }
        }
        return '';
    }

    /**
     * 
     * @param  string         $interface       network interface to bind to
     * @param  string         $secureInterface same as `$interfaces` but using secure certificates
     * @param  string         $api             api directory
     * @param  string         $www             static assets directory
     * @param  string         $apiPrefix       a prefix to add to the api path
     * @return Unsafe<Server>
     */
    public static function create(
        string $interface = '127.0.0.1:8080',
        string $secureInterface = '',
        string $api = './server/api/',
        string $www = './server/www/',
        string $apiPrefix = '',
    ): Unsafe {
        if(!str_starts_with($api, './')){
            return error("The api directory must be a relative path and within the project directory.");
        }
        if(!str_starts_with($www, './')){
            return error("The web root directory must be a relative path and within the project directory.");
        }
        $api = preg_replace('/\/+$/', '', $api);
        $www = preg_replace('/\/+$/', '', $www);

        /** @var Unsafe<LoggerInterface> */
        $loggerAttempt = Container::create(LoggerInterface::class);
        if ($loggerAttempt->error) {
            return error($loggerAttempt->error);
        }

        $logger = $loggerAttempt->value;

        $info = (new Option("--info"))->findValue("bool") ?? false;

        if (!str_starts_with($apiPrefix, "/")) {
            $apiPrefix = "/$apiPrefix";
        }


        if ((!$www = self::findFirstValidWebDirectory([$www])) && $info) {
            $logger->warning("Could not find a valid web root directory.");
        }

        if ((!$api = self::findFirstValidRoutesDirectory([$api])) && $info) {
            $logger->warning("Could not find a valid api directory.");
        }
        
        return ok(new self(
            interface: $interface,
            secureInterface: $secureInterface,
            apiPrefix: $apiPrefix,
            www: $www,
            api: $api,
            router: Router::create(),
            logger: $logger,
        ));
    }

    private SocketHttpServer $server;
    private RouteResolver $resolver;
    private FileServerInterface $fileServer;
    /** @var array<Middleware> */
    private array $middlewares = [];
    public SessionOperationsInterface $sessionOperations;

    private function __construct(
        public readonly string $interface,
        public readonly string $secureInterface,
        public readonly string $apiPrefix,
        public readonly string $api,
        public readonly string $www,
        public readonly Router $router,
        public readonly LoggerInterface $logger,
    ) {
        $initialisationAttempt = self::initializeRoutes(
            logger: $this->logger,
            router: $router,
            apiPrefix: $this->apiPrefix,
            api: $this->api,
        );

        if ($initialisationAttempt->error) {
            $logger->error($initialisationAttempt->error->getMessage());
        }

        if (!Session::getOperations()) {
            Session::setOperations(
                FileSystemSessionOperations::create(
                    ttl      : 1_440,
                    directoryName: ".sessions",
                    keepAlive: false,
                )
            );
        }

        $this->sessionOperations = Session::getOperations();


        Bootstrap::onKill(function() {
            $this->stop();
        });

        $this->resolver = RouteResolver::create($this);
    }

    public function appendMiddleware(Middleware $middleware) {
        $this->middlewares[] = $middleware;
    }

    public function setFileServer(FileServerInterface $fileServer):self {
        $this->fileServer = $fileServer;
        return $this;
    }

    /**
     * Get the current router used by the user.
     * @return Router
     */
    public function getRouter():Router {
        return $this->router;
    }

    /**
     * Start the server.
     * 
     * This method will await untill one of the following signals is sent to the program `SIGHUP`, `SIGINT, `SIGQUIT`, `SIGTERM`.
     * @return Future<Unsafe<void>>
     */
    public function start():Future {
        return async(function() {
            try {
                $stopper = function(string $callbackId) {
                    EventLoop::cancel($callbackId);
                    $this->stop();
                    Bootstrap::kill();
                };
    
                EventLoop::onSignal(SIGHUP, $stopper);
                EventLoop::onSignal(SIGINT, $stopper);
                EventLoop::onSignal(SIGQUIT, $stopper);
                EventLoop::onSignal(SIGTERM, $stopper);
    
                /** @var Unsafe<LoggerInterface> */
                $loggerAttempt = Container::create(LoggerInterface::class);
                if ($loggerAttempt->error) {
                    return error($loggerAttempt->error);
                }
    
                $endSignal      = new DeferredFuture;
                $logger         = $loggerAttempt->value;
                $requestHandler = ServerRequestHandler::create($logger, $this->fileServer, $this->resolver);
                $stackedHandler = stackMiddleware($requestHandler, ...$this->middlewares);
                $errorHandler   = ServerErrorHandler::create($logger);
                $this->server   = SocketHttpServer::createForDirectAccess($logger);
                $this->server->onStop(static fn () => $endSignal->complete());
                $this->server->expose($this->interface);
                $this->server->start($stackedHandler, $errorHandler);
                $endSignal->getFuture()->await();
                return ok();
            } catch (Throwable $e) {
                return error($e);
            }
        });
    }

    /**
     * 
     * Stop the server.
     * @return void
     */
    public function stop(): void {
        if (isset($this->server)) {
            $this->server->stop();
        }
    }

    private static function initializeRoutes(
        LoggerInterface $logger,
        Router $router,
        string $apiPrefix,
        string $api,
    ): Unsafe {
        if ($api) {
            if(isPhar()){
                $api = Phar::running()."/$api";
            }
            
            $flatListAttempt = Directory::flat($api);
            if ($flatListAttempt->error) {
                return error($flatListAttempt->error);
            }

            foreach ($flatListAttempt->value as $fileName) {
                if (!str_ends_with(strtolower($fileName), '.php')) {
                    continue;
                }
                $offset       = strpos($fileName, $api);
                $offset       = $offset?$offset:0;
                $relativePath = substr($fileName, $offset + strlen($api));
                
                if (!str_starts_with($relativePath, '.'.DIRECTORY_SEPARATOR)) {
                    if ($handler = require_once $fileName) {
                        $fileName = preg_replace('/\.php$/i', '', preg_replace('/\.\/+/', '/', '.'.DIRECTORY_SEPARATOR.$relativePath));
                        
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            $fileName = preg_replace('/\\\\/i', '/', $fileName);
                        }

                        $symbolicMethod = preg_replace('/^\\//', '', strtoupper(preg_replace('/^.*(?=\/)/', '', $fileName)));
                        $symbolicPath   = preg_replace('/\\/$/', '', preg_replace('/(?<=\/)[^\/]*$/', '', "$apiPrefix$fileName"))?:'/';

                        $routeExists = $router->routeExists($symbolicMethod, $symbolicPath);

                        if (!$routeExists) {
                            $cwd = dirname($api.$fileName)?:'';
                            $router->initialize($symbolicMethod, $symbolicPath, $handler, $cwd);
                        } else {
                            $logger->info("Route $symbolicMethod $symbolicPath already exists. Will not overwrite.");
                        }
                    }
                }
            }
        }
        return ok();
    }
}