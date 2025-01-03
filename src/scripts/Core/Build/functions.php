<?php
namespace CatPaw\Core\Build;

use function Amp\File\isDirectory;
use CatPaw\Core\Directory;
use function CatPaw\Core\env;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use CatPaw\Core\Process;
use CatPaw\Core\Result;
use Exception;
use Phar;

/**
 *
 * @param  bool         $optimize
 * @return Result<None>
 */
function build(bool $optimize = false):Result {
    if (ini_get('phar.readonly')) {
        return error('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
    }

    $name        = env('name') ?: 'app.phar';
    $main        = env('main');
    $libraries   = env('libraries');
    $match       = env('match');
    $environment = env('environment');

    if (!$main) {
        return error(join("\n", [
            "Entry file is missing from environment.",
            "Remember to properly load your build configuration using `--environment=build.ini`.",
        ]));
    }

    $name      = str_replace(['"',"\n"], ['\\"',''], $name);
    $main      = str_replace(['"',"\n"], ['\\"',''], $main);
    $libraries = str_replace(['"',"\n"], ['\\"',''], $libraries);

    if ($environment) {
        $environment = str_replace(['"',"\n"], ['\\"',''], $environment);
    }

    if (!str_ends_with(strtolower($name), '.phar')) {
        $name .= '.phar';
    }

    $app          = "$name";
    $start        = '.build-cache/start.php';
    $dirnameStart = dirname($start);

    try {
        if (File::exists($dirnameStart)) {
            Directory::delete($dirnameStart)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        Directory::create($dirnameStart)->unwrap($error);

        if ($error) {
            return error($error);
        }

        $environmentFallbackStringified = $environment ? "\Phar::running().'/'.'$environment'":"''";

        $file = File::open($start, 'w+')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $file->write(<<<PHP
            <?php
            use CatPaw\Core\Commands\ApplicationBundledCommand;
            use CatPaw\Core\Container;
            use CatPaw\Core\Implementations\CommandRegister\SimpleCommandRegister;
            use CatPaw\Core\Interfaces\CommandRegisterInterface;

            require 'vendor/autoload.php';

            Container::provide(CommandRegisterInterface::class, \$command = new SimpleCommandRegister);
            \$command->register(new ApplicationBundledCommand(
                main: "$main",
                name: "$name",
                libraries: "$libraries",
                resources: '',
                environment: $environmentFallbackStringified,
            ))->unwrap(\$error) or die(\$error);
            PHP)->unwrap($error);

        $file->close();

        if ($error) {
            return error($error);
        }


        // die($output.PHP_EOL);

        if (File::exists($app)) {
            File::delete($app)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if (File::exists($app.'.gz')) {
            File::delete($app.'.gz')->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if ($optimize) {
            Process::execute("composer update --no-dev", out())->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if (isDirectory("./vendor/bin")) {
            Directory::delete("./vendor/bin")->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        $phar = new Phar($app);

        $phar->startBuffering();

        $phar->buildFromDirectory('.', $match);

        $phar->setStub(
            "#!/usr/bin/env php \n".$phar->createDefaultStub($start)
        );

        $phar->stopBuffering();

        $phar->compressFiles(Phar::GZ);

        # Make the file executable
        chmod($app, 0770);

        Directory::delete($dirnameStart)->unwrap($error);
        if ($error) {
            return error($error);
        }

        if ($optimize) {
            Process::execute("composer update", out())->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        echo "$app successfully created".PHP_EOL;
        return ok();
    } catch (Exception $e) {
        return error($e);
    }
}
