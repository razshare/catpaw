<?php
namespace CatPaw\Core\Build;

use function Amp\File\isDirectory;

use CatPaw\Core\Container;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use function CatPaw\Core\execute;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use CatPaw\Core\Unsafe;
use Exception;
use Phar;
use Psr\Log\LoggerInterface;

/**
 *
 * @param  false|string $buildConfig
 * @param  bool         $buildConfigInit
 * @param  bool         $buildOptimize
 * @return Unsafe<void>
 */
function build(
    false|string $buildConfig = false,
    bool $buildConfigInit = false,
    bool $buildOptimize = false,
):Unsafe {
    if ($buildConfig) {
        if (!File::exists($buildConfig)) {
            $variants = [];

            if (str_ends_with($buildConfig, '.yml')) {
                $variants[] = substr($buildConfig, -3).'.yaml';
            } else if (str_ends_with($buildConfig, '.yaml')) {
                $variants[] = substr($buildConfig, -5).'.yml';
            } else {
                $variants[] = "$buildConfig.yaml";
                $variants[] = "$buildConfig.yml";
            }

            foreach ($variants as $variant) {
                if (!str_starts_with($variant, '/') && !str_starts_with($variant, '../') && !str_starts_with($variant, './')) {
                    $variant = "./$variant";
                }

                if (File::exists($variant)) {
                    $buildConfig = $variant;
                    break;
                }
            }
        }
    }


    if (File::exists('build.yaml')) {
        $buildConfig = $buildConfig?:'build.yaml';
    } else {
        $buildConfig = $buildConfig?:'build.yml';
    }


    $logger = Container::create(LoggerInterface::class)->try($error);
    if ($error) {
        return error($error);
    }

    if ($buildConfigInit) {
        $logger->info('Trying to generate build.yaml file...');

        if (!File::exists('build.yaml') && !File::exists('build.yml')) {
            $file = File::open('build.yaml')->try($error);
            if ($error) {
                return error($error);
            }

            $file->write('build.yaml', <<<YAML
                name: app
                entry: ./src/main.php
                libraries: ./src/lib
                environment: ./env.yaml
                match: /(^\.\/(\.build-cache|src|vendor|bin)\/.*)|(^\.\/(\.env|env\.yaml|env\.yml))/
                YAML)->await()->try($error);

            if ($error) {
                return error($error);
            }

            $logger->info('done!');
        } else {
            $logger->info('A build.yaml file already exists - will not overwrite.');
        }
    }

    if (ini_get('phar.readonly')) {
        return error('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
    }

    $file = File::open($buildConfig)->try($error);
    if ($error) {
        return error($error);
    }

    $content = $file->readAll()->await()->try($error);
    if ($error) {
        return error($error);
    }

    $config = yaml_parse($content);

    /**
     * @var string      $name
     * @var string      $entry
     * @var string      $libraries
     * @var string      $match
     * @var null|string $environment
     */
    $name        = $config['name']        ?? 'app.phar';
    $entry       = $config['entry']       ?? '';
    $libraries   = $config['libraries']   ?? '';
    $match       = $config['match']       ?? '';
    $environment = $config['environment'] ?? null;

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

        $environmentFallbackStringified = $environment ? "'$environment'":"''";

        $file = File::open($start, 'w+')->try($error);
        if ($error) {
            return error($error);
        }

        $writeAttempt = $file->write(<<<PHP
            <?php
            use CatPaw\Core\Attributes\Option;
            use CatPaw\Core\Bootstrap;
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
