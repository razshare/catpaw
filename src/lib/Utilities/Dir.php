<?php
namespace CatPaw\Utilities;

use function filemtime;
use function filesize;
use InvalidArgumentException;
use function is_dir;
use function preg_replace;
use function scandir;

abstract class Dir {

    /**
     * Alias of findFilesRecursive
     * Get the filenames within a directory recursively.
     * @param string     $root startup directory.
     * @param array|null $map  an associative array that will hold your results.
     */
    public static function getFilenamesRecursive(string $root,?array &$map):void {
        static::findFilesRecursive($root,$map);
    }

    /**
     * Get the filenames within a directory recursively.
     * @param string     $root startup directory.
     * @param array|null $map  an associative array that will hold your results.
     */
    public static function findFilesRecursive(string $root,?array &$map):void {
        $root = preg_replace('/\/++/','/',$root);
        //$fn = end(explode("/",$root));
        if (is_dir($root)) {
            $scan = scandir($root);
            foreach ($scan as $a => $file) {
                if ("." == $file || ".." == $file || ".git" == $file) {
                    continue;
                }
                self::getFilenamesRecursive("$root/$file",$map);
            }
        } else {
            $map[] = [
                "name" => $root,
                "size" => filesize($root),
                "lastChange" => filemtime($root)
            ];
        }
    }

    /**
     * Get the contents of a directory in one single string recursively.
     * @param  string       $root         the directory to be resolved
     * @param  int          $lastModified an pointer to an initialized integer.
     *                                    The method will update this pointer with the unix timestamp of the last change
     *                                    in the given directory.
     * @return array|string
     */
    public static function getContentsRecursive(string $root,int &$lastModified) {
        //$fn = end(explode("/",$root));
        if (is_dir($root)) {
            $scan = scandir($root);
            $result = [];
            foreach ($scan as $a => $file) {
                if ("." == $file || ".." == $file || ".git" == $file) {
                    continue;
                }
                $result[$file] = self::getContentsRecursive("$root/$file",$lastModified);
            }
            return $result;
        } else {
            $tmpTime = filemtime($root);
            if ($tmpTime > $lastModified) {
                $lastModified = $tmpTime;
            }
            return file_get_contents($root);
        }
    }

    /**
     * Remove a directory (recursively?).
     * @param  string                   $dirPath     name of the directory.
     * @param  bool                     $recursively if true will try remove all sub directories aswell.<br />
     *                                               <b>NOTE</b>: will fail if false and subdirectories are present.
     * @throws InvalidArgumentException if the specified directory name is not actually a directory.
     * @return void
     */
    public static function remove(string $dirPath, bool $recursively = false):void {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }
        $files = glob($dirPath.'*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file) && $recursively) {
                self::remove($file, true);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}