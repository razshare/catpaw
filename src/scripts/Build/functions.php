<?php
namespace CatPaw\Core\Build;

use function Amp\File\isDirectory;

use CatPaw\Core\Directory;
use function CatPaw\Core\env;
use function CatPaw\Core\error;
use function CatPaw\Core\execute;
use CatPaw\Core\File;

use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use CatPaw\Core\Unsafe;
use Exception;
use Phar;

/**
 *
 * @param  bool         $buildOptimize
 * @param  false|string $buildConfig
 * @param  bool         $buildConfigInit
 * @return Unsafe<void>
 */
function build(
    bool $buildOptimize = false,
):Unsafe {
    if (ini_get('phar.readonly')) {
        return error('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
    }

    /**
     * @var string $name
     * @var string $entry
     * @var string $libraries
     * @var string $match
     * @var string $environment
     */
    $name        = env('name')        ?? 'app.phar';
    $entry       = env('entry')       ?? '';
    $libraries   = env('libraries')   ?? '';
    $match       = env('match')       ?? '';
    $environment = env('environment') ?? '';

    if (!$entry) {
        return error(join("\n", [
            "Entry file is missing from environment.",
            "Remember to properly load your build configuration using `--environment=\"./build.yaml\"`.",
        ]));
    }

    $name      = str_replace(['"',"\n"], ['\\"',''], $name);
    $entry     = str_replace(['"',"\n"], ['\\"',''], $entry);
    $libraries = str_replace(['"',"\n"], ['\\"',''], $libraries);

    if ($environment) {
        $environment = str_replace(['"',"\n"], ['\\"',''], $environment);
    }

    if (!str_ends_with(strtolower($name), '.phar')) {
        $name .= '.phar';
    }

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if (!str_starts_with($entry, './')) {
        if (!$isWindows) {
            return error("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
        }
        if (!str_starts_with($entry, '.\\')) {
            return error("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
        }
    }

    $app          = "$name";
    $start        = '.build-cache/start.php';
    $dirnameStart = dirname($start);

    try {
        if (File::exists($dirnameStart)) {
            Directory::delete($dirnameStart)->try($error);
            if ($error) {
                return error($error);
            }
        }

        Directory::create($dirnameStart)->try($error);
        if ($error) {
            return error($error);
        }

        $environmentFallbackStringified = $environment ? "\Phar::running().'/'.'$environment'":"''";

        $file = File::open($start, 'w+')->try($error);
        if ($error) {
            return error($error);
        }

        $writeAttempt = $file->write(<<<PHP
            <?php
            use CatPaw\Core\Attributes\Option;
            use CatPaw\Core\Bootstrap;

            require 'vendor/autoload.php';

            \$environment = new Option('--environment');
            \$environment = \$environment->findValue('string', true)??'';

            if(!\$environment){
                \$environment = $environmentFallbackStringified;
            }

            Bootstrap::start(
                entry: "$entry",
                name: "$name",
                libraries: "$libraries",
                resources: '',
                environment: \$environment,
                dieOnChange: false,
            );
            PHP)->await()->try($error);

        $file->close();

        if ($error) {
            return error($error);
        }


        // die($output.PHP_EOL);

        if (File::exists($app)) {
            File::delete($app)->try($error);
            if ($error) {
                return error($error);
            }
        }

        if (File::exists($app.'.gz')) {
            File::delete($app.'.gz')->try($error);
            if ($error) {
                return error($error);
            }
        }

        if ($buildOptimize) {
            execute("composer update --no-dev", out())->await()->try($error);
            if ($error) {
                return error($error);
            }
        }

        if (isDirectory("./vendor/bin")) {
            Directory::delete("./vendor/bin")->try($error);
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

        Directory::delete($dirnameStart)->try($error);
        if ($error) {
            return error($error);
        }

        if ($buildOptimize) {
            execute("composer update", out())->await()->try($error);
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
