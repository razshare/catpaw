<?php
namespace CatPaw\Build;

use function Amp\File\isDirectory;

use CatPaw\Directory;
use function CatPaw\error;
use function CatPaw\execute;
use CatPaw\File;
use function CatPaw\ok;
use function CatPaw\out;
use CatPaw\Unsafe;
use Exception;
use Phar;

/**
 * 
 * @param string $config name of the build yaml file.
 * 
 * Multiple names separated by "," are allowed, only the first valid name will be used.
 * @return Unsafe<void>
 */
function build(
    string $buildFile,
    bool $optimize,
):Unsafe {
    $fileAttempt = File::open($buildFile, 'r');
    if ($fileAttempt->error) {
        return error($fileAttempt->error);
    }
    
    $readAttempt = $fileAttempt->value->readAll()->await();
    if ($readAttempt->error) {
        return error($readAttempt->error);
    }

    $config = yaml_parse($readAttempt->value);

    /**
     * @var string      $name
     * @var string      $entry
     * @var string      $libraries
     * @var string      $match
     * @var null|string $environment
     * @var null|bool   $info
     */
    $name        = $config['name']        ?? 'app.phar';
    $entry       = $config['entry']       ?? '';
    $libraries   = $config['libraries']   ?? '';
    $match       = $config['match']       ?? '';
    $environment = $config['environment'] ?? null;
    $info        = $config['info']        ?? null;

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

    $app   = "$name";
    $start = '.build-cache/start.php';
    $dirnameStart = dirname($start);
    
    try {
        if (File::exists($dirnameStart)) {
            $delteAttmpet = Directory::delete($dirnameStart);
            if ($delteAttmpet->error) {
                return error($delteAttmpet->error);
            }
        }

        if ($error = Directory::create($dirnameStart)->error) {
            return error($error);
        }

        $environmentFallbackStringified = $environment ? "'$environment'":"''";

        $infoFallbackStringified = $info ? 'true':'false';
        
        $fileAttempt = File::open($start, 'w+');
        if ($fileAttempt->error) {
            return error($fileAttempt->error);
        }

        $writeAttempt = $fileAttempt->value->write($output = <<<PHP
            <?php
            use CatPaw\Attributes\Option;
            use CatPaw\Bootstrap;
            \$_ENV = [
                ...\$_ENV,
                ...getenv(),
            ];
            require 'vendor/autoload.php';

            \$environment = new Option('--environment');
            \$environment = \$environment->findValue('string', true);

            if(null === \$environment){
                \$environment = \Phar::running().'/'.$environmentFallbackStringified;
            }

            \$info = new Option('--info');
            \$info = \$info->findValue('bool', true) ?? $infoFallbackStringified;

            Bootstrap::start(
                entry: "$entry",
                name: "$name",
                libraries: "$libraries",
                resources: '',
                environment: \$environment,
                info: \$info,
                dieOnChange: false,
            );
            PHP)->await();
        
        $fileAttempt->value->close();

        if ($writeAttempt->error) {
            return error($writeAttempt->error);
        }


        // die($output.PHP_EOL);
        
        if (File::exists($app)) {
            if ($error = File::delete($app)->error) {
                return error($error);
            }
        }

        if (File::exists($app.'.gz')) {
            if ($error = File::delete($app.'.gz')->error) {
                return error($error);
            }
        }
        
        if ($optimize) {
            if ($error = execute("composer update --no-dev", out())->await()->error) {
                return error($error);
            }
        }
        
        if (isDirectory("./vendor/bin")) {
            if ($error = Directory::delete("./vendor/bin")->error) {
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

        if ($error = Directory::delete($dirnameStart)->error) {
            return error($error);
        }

        if ($optimize) {
            if ($error = execute("composer update", out())->await()->error) {
                return error($error);
            }
        }

        echo "$app successfully created".PHP_EOL;
        return ok();
    } catch (Exception $e) {
        return error($e);
    }
}