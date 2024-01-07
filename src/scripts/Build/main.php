<?php
namespace CatPaw\Build;

use CatPaw\Attributes\Option;
use function CatPaw\error;
use CatPaw\File;

use CatPaw\Unsafe;


/**
 * @param  bool         $initConfig
 * @param  false|string $config
 * @param  bool         $optimizeLong
 * @param  bool         $optimizeShort
 * @return Unsafe<void>
 */
function main(
    #[Option("--init-config")]
    bool $initConfig = false,
    #[Option("--config")]
    false|string $config = false,
    #[Option("--optimize")]
    bool $optimizeLong = false,
    #[Option("-o")]
    bool $optimizeShort = false,
):Unsafe {
    $optimized = $optimizeLong || $optimizeShort;

    if ($initConfig) {
        echo 'Trying to generate build.yml file...';
        
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
            
            echo 'done!'.PHP_EOL;
        } else {
            echo 'a build.yml file already exists - will not overwrite.'.PHP_EOL;
        }
    }

    if (ini_get('phar.readonly')) {
        return error('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
    }

    if (File::exists('build.yml')) {
        return build($config?:'build.yml', $optimized);
    } else if (File::exists('build.yaml')) {
        return build($config?:'build.yaml', $optimized);
    }
    return error("Build file not found.");
}