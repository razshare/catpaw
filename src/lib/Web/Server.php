<?php
namespace CatPaw\Web;

use CatPaw\Attributes\Option;
use CatPaw\Bootstrap;
use CatPaw\Container;
use CatPaw\Directory;
use function CatPaw\error;
use CatPaw\File;

use function CatPaw\isDirectory;
use function CatPaw\isPhar;
use function CatPaw\ok;

use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Session;
use CatPaw\Web\Interfaces\FileServerInterface;
use Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\SocketServer;

use Revolt\EventLoop\UnsupportedFeatureException;

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
        $api = realpath($api);
        if (false === $api) {
            return error("Invalid api directory.");
        }
        $www = realpath($www);
        if (false === $www) {
            return error("Invalid web root directory.");
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

    private FileServerInterface $fileServer;
    private RouteResolver $resolver;
    private SocketServer $socket;
    private HttpServer $httpServer;
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
     * @throws Error
     * @throws Throwable
     * @throws CompositeException
     * @throws UnsupportedFeatureException
     * @return Promise<Unsafe<void>>
     */
    public function start():PromiseInterface {
        return new Promise(function($ok) {
            $stopper = false;
            $stopper = function() use ($ok, &$stopper) {
                Loop::removeSignal(SIGHUP, $stopper);
                Loop::removeSignal(SIGINT, $stopper);
                Loop::removeSignal(SIGQUIT, $stopper);
                Loop::removeSignal(SIGTERM, $stopper);
                $this->stop();
                $ok(ok());
            };

            /** @var Unsafe<LoggerInterface> */
            $loggerAttempt = Container::create(LoggerInterface::class);
            if ($loggerAttempt->error) {
                return error($loggerAttempt->error);
            }

            $logger = $loggerAttempt->value;

            $this->httpServer = new HttpServer($this->respond(...));
            $this->socket     = new SocketServer($this->interface);

            $this->httpServer->listen($this->socket);

            $logger->info("Server started at http://$this->interface");

            $ok(ok());
        });
    }

    /**
     * 
     * Stop the server.
     * @return void
     */
    public function stop(): void {
        if (isset($this->socket)) {
            $this->socket->close();
        }
    }

    private static function initializeRoutes(
        LoggerInterface $logger,
        Router $router,
        string $apiPrefix,
        string $api,
    ): Unsafe {
        if ($api) {
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

    /**
     * 
     * @param  RequestInterface                                  $request
     * @param  HttpInvoker                                       $invoker
     * @param  LoggerInterface                                   $logger
     * @param  callable(RequestInterface,ResponseInterface):void $fileServer
     * @throws \Throwable
     * @throws \ReflectionException
     * @return ResponseInterface
     */
    private function respond(RequestInterface $request):ResponseInterface {
        try {
            $responseFromFileServer = $this->fileServer->serve($request);
            if (HttpStatus::NOT_FOUND === $responseFromFileServer->getStatusCode()) {
                $responseAttempt = $this->resolver->resolve($request);
                if ($responseAttempt->error) {
                    throw $responseAttempt->error;
                }
                return $responseAttempt->value;
            }
            return $responseFromFileServer;
        } catch (Throwable $e) {
            $message    = $e->getMessage();
            $fileName   = $e->getFile();
            $lineNumber = $e->getLine();
            $this->logger->error("$message", [
                "file" => $fileName,
                "line" => $lineNumber,
            ]);
            return new Response(HttpStatus::INTERNAL_SERVER_ERROR, [], HttpStatus::getReason(HttpStatus::INTERNAL_SERVER_ERROR));
        }
    }
}