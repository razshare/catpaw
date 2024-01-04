<?php

namespace CatPaw\Web;

use Amp\Socket\Certificate;
use Parsedown;

class HttpConfiguration {
    /** @var array<string> List of interfaces to bind to. */
    public array $interfaces = ['127.0.0.1:8080'];

    /** @var array<string> List of secure interfaces to bind to (requires pemCertificate). */
    public array $secureInterfaces = [];

    /** @var string Directory the application should serve. */
    public string $www = 'www';

    /** @var array<string,Certificate> an array mapping domain names to pem certificates. */
    public array $certificates = [];

    /** @var bool This dictates if the stack trace should be shown to the client whenever an Exceptions is caught or not. */
    public bool $showStackTrace = false;

    /** @var bool This dictates if exceptions should be shown to the client whenever an Exceptions is caught or not. */
    public bool $showExceptions = false;

    /** @var false|Parsedown Markdown parser */
    public false|Parsedown $makrup = false;

    /**
     * @return array<string,string>
     */
    public function defaultCacheHeaders():array {
        return [
            'Cache-Control' => 'max-age=604800, public, must-revalidate, stale-while-revalidate=86400',
        ];
    }

    public bool $redirectToSecure = false;

    /**
     * Default headers for static assets.
     * @var array
     */
    public array $headers = [];

    /**
     * A list of directories containing route handlers.
     * @var array
     */
    public array $routes = [];
}
