<?php

namespace CatPaw\Web;

use function CatPaw\error;
use function CatPaw\ok;
use CatPaw\ReflectionTypeManager;

use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Param;

class PathResolver {
    private static $cache = [];

    /**
     * @return Unsafe<void>
     */
    public static function cacheResolver(string $symbolicMethod, string $symbolicPath, array $parameters):Unsafe {
        if ($error = self::findResolver($symbolicMethod, $symbolicPath, $parameters)->error) {
            return error($error);
        }

        return ok();
    }

    /**
     * @return Unsafe<MatchingPathConfiguration>
     */
    public static function findResolver(string $symbolicMethod, string $symbolicPath, array $parameters):Unsafe {
        $key = "$symbolicMethod:$symbolicPath";
        if (isset(self::$cache[$key])) {
            return ok(self::$cache[$key]);
        }

        $cofigurations = self::findMatchingPathConfigurations($symbolicPath, $parameters);
        if ($cofigurations->error) {
            return error($cofigurations->error);
        }
        return ok(self::$cache[$key] = new PathResolver($cofigurations->value));
    }



    /**
     * @return Unsafe<array<MatchingPathConfiguration>>
     */
    public static function findMatchingPathConfigurations(string $path, array $reflectionParameters):Unsafe {
        /** @var array<MatchingPathConfiguration> */
        $configurations           = [];
        $reflectionParametrsNames = [];

        foreach ($reflectionParameters as $reflectionParameter) {
            $reflectionParametrName     = $reflectionParameter->getName();
            $reflectionParametrsNames[] = $reflectionParametrName;
            /** @var Unsafe<Param> */
            $param = Param::findByParameter($reflectionParameter);
            if ($param->error) {
                return error($param->error);
            }
            if (!$param->value) {
                continue;
            }

            $typeName = ReflectionTypeManager::unwrap($reflectionParameter) ?? 'string';

            if ('' === $param->value->getRegex()) {
                switch ($typeName) {
                    case 'int':
                        $param->value->setRegex('[-+]?[0-9]+');
                        break;
                    case 'float':
                        $param->value->setRegex('[-+]?[0-9]+\.[0-9]+');
                        break;
                    case 'string':
                        $param->value->setRegex('[^\/]*');
                        break;
                    case 'bool':
                        $param->value->setRegex('(0|1|no?|y(es)?|false|true)');
                        break;
                }
            }

            $configurations[] = new MatchingPathConfiguration(
                param: $param->value,
                name: $reflectionParametrName,
                namePattern: '\{'.$reflectionParametrName.'\}',
                isStatic: false,
            );
        }
        
        $result = [];

        if (preg_match_all('/{([^\{\}]+)}/', $path, $matches)) {
            foreach ($matches[1] as $key => $match) {
                if (!$configuration = $configurations[$key] ?? false) {
                    $param         = new Param('[^\/]*');
                    $configuration = new MatchingPathConfiguration(
                        param: $param,
                        name: $match,
                        namePattern: '\{'.$match.'\}',
                        isStatic: false,
                    );
                } else if (!MatchingPathConfiguration::findInArrayByName($configurations, $configuration)) {
                    $param         = new Param('[^\/]*');
                    $configuration = new MatchingPathConfiguration(
                        param: $param,
                        name: $match,
                        namePattern: '\{'.$match.'\}',
                        isStatic: false,
                    );
                }

                $result[] = $configuration;
            }
        }


        return ok(MatchingPathConfiguration::mergeWithPath($result, $path));
    }

    /**
     * 
     * @param  array<MatchingPathConfiguration> $configurations
     * @return void
     */
    public function __construct(private array $configurations) {
    }

    public function findParametersFromPath(string $path):false|array {
        return MatchingPathConfiguration::findParametersFromPath($this->configurations, $path);
    }
}