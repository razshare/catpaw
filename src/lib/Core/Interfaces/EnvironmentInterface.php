<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface EnvironmentInterface {
    /**
     * Merge `$_ENV` and `getenv()` with this service's internal variables map.\
     * \
     * This may overwrite keys defined in your environment file.\
     * Call `load()` again to recover the lost keys.
     * @return void
     */
    public function includeSystemEnvironment():void;

    /**
     * Set the environment file name.
     * @param  string $fileName
     * @return void
     */
    public function setFileName(string $fileName):void;

    /**
     * Clear all environment variables.
     * @return void
     */
    public function clear():void;

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @return Unsafe<None>
     */
    public function load():Unsafe;


    /**
     * Set an environment property.
     * @param  string $query The name of the property.\
     *                       It can be a deep path separated by `.` (period), for example
     *                       ```php
     *                       $environment->set('database.username','admin');
     *                       ```
     * @param  mixed  $value
     * @return void
     */
    public function set(string $query, mixed $value):void;


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
     * @return mixed  The value of the property.
     */
    public function get(string $query):mixed;
}