<?php
namespace CatPaw\Build;

use function Amp\File\isDirectory;

use CatPaw\Container;
use CatPaw\Directory;
use function CatPaw\error;
use function CatPaw\execute;
use CatPaw\File;
use function CatPaw\ok;
use function CatPaw\out;
use CatPaw\Unsafe;
use Exception;
use Phar;
use Psr\Log\LoggerInterface;

/**
 *
 * @param false|string $buildConfig
 * @param bool         $buildConfigInit
 * @param bool         $buildOptimize
 * @return Unsafe<void>
 */
function build(
    false|string $buildConfig = false,
    bool $buildConfigInit = false,
    bool $buildOptimize = false,
):Unsafe {
    if (File::exists('build.yml')) {
        $buildConfig = $buildConfig?:'build.yml';
    } else {
        $buildConfig = $buildConfig?:'build.yaml';
    }


    /** @var Unsafe<LoggerInterface> $loggerAttempt */
    $loggerAttempt = Container::create(LoggerInterface::class);
    if ($loggerAttempt->error) {
        return error($loggerAttempt->error);
    }
    $logger = $loggerAttempt->value;

    if ($buildConfigInit) {
        $logger->info('Trying to generate build.yml file...');
        
        if (!File::exists('build.yml')) {
            $file = File::open('build.yml');
            if ($file->error) {
                return error($file->error);
            }

            $writeAttempt = $file->value->write('build.yml', <<<YAML
                name: app
                entry: ./src/main.php
                libraries: ./src/lib
                environment: ./env.yml
                info: false
                match: /(^\.\/(\.build-cache|src|vendor|resources|bin)\/.*)|(\.\/env\.yml)/
                YAML)->await();

            if ($writeAttempt->error) {
                return error($writeAttempt->error);
            }
            
            $logger->info('done!');
        } else {
            $logger->info('A build.yml file already exists - will not overwrite.');
        }
    }

    if (ini_get('phar.readonly')) {
        return error('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
    }




    $fileAttempt = File::open($buildConfig);
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

    $app          = "$name";
    $start        = '.build-cache/start.php';
    $dirnameStart = dirname($start);
    
    try {
        if (File::exists($dirnameStart)) {
            $deleteAttempt = Directory::delete($dirnameStart);
            if ($deleteAttempt->error) {
                return error($deleteAttempt->error);
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

        $writeAttempt = $fileAttempt->value->write(<<<PHP
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
        
        if ($buildOptimize) {
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

        if ($buildOptimize) {
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