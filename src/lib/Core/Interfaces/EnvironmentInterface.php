<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface EnvironmentInterface {
    /**
     * Merge `$_ENV` and `getenv()` with this service's internal variables map.\
     * \
     * This may overwrite keys defined in your environment file.\
     * Call `load()` again to recover the lost keys.
     * @return self
     */
    public function includeSystemEnvironment():self;

    /**
     * Set the environment file name.
     * @param  string $fileName
     * @return self
     */
    public function withFileName(string $fileName):self;

    /**
     * Clear all environment variables.
     * @return void
     */
    public function clear():void;

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @return Result<None>
     */
    public function load():Result;


    /**
     * Set an environment property.
     * @param  string $query The name of the property.\
     *                       It can be a deep path separated by `.` (period), for example
     *                       ```php
     *                       $environment->set('database.username','admin');
     *                       ```
     * @param  mixed  $value
     * @return self
     */
    public function set(string $query, mixed $value):self;


    /**
     * Find an environment property by name.
     *
     * ## Example
     * ```php
     * $service->get("server")['www'];
     * // or better even
     * $service->$get("server.www");
     * ```
     * @param  string $query The name of the property. It can be a deep path separated by `.` (period), for example
     *                       ```php
     *                       $environment->set('database.username','admin');
     *                       ```
     * @return string The value of the property.
     */
    public function get(string $query):string;
}