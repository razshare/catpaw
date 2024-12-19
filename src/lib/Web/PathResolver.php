<?php
namespace CatPaw\Web;

use function CatPaw\Core\error;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Web\Attributes\Param;
use ReflectionParameter;

class PathResolver {
    /** @var array<string,PathResolver> */
    private static array $cache = [];


    /**
     *
     * @param  string                     $symbolicMethod
     * @param  string                     $symbolicPath
     * @param  array<ReflectionParameter> $parameters
     * @return Result<None>
     */
    // @phpstan-ignore-next-line
    public static function cacheResolver(string $symbolicMethod, string $symbolicPath, array $reflectionParameters):Result {
        self::findResolver($symbolicMethod, $symbolicPath, $reflectionParameters)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }

    /**
     *
     * @param  string                     $symbolicMethod
     * @param  string                     $symbolicPath
     * @param  array<ReflectionParameter> $reflectionParameters
     * @return Result<PathResolver>
     */
    public static function findResolver(string $symbolicMethod, string $symbolicPath, array $reflectionParameters):Result {
        $key = "$symbolicMethod:$symbolicPath";
        if (isset(self::$cache[$key])) {
            return ok(self::$cache[$key]);
        }

        $configurations = self::findMatchingPathConfigurations($symbolicPath, $reflectionParameters)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $resolver = self::$cache[$key] = new PathResolver($configurations);

        return ok($resolver);
    }




    /**
     *
     * @param  string                                   $path
     * @param  array<ReflectionParameter>               $reflectionParameters
     * @return Result<array<MatchingPathConfiguration>>
     */
    public static function findMatchingPathConfigurations(string $path, array $reflectionParameters):Result {
        /** @var array<MatchingPathConfiguration> $configurations */
        $configurations = [];

        foreach ($reflectionParameters as $reflectionParameter) {
            $reflectionParameterName = $reflectionParameter->getName();
            /** @var false|Param $param */
            $param = Param::findByParameter($reflectionParameter)->unwrap($error);
            if ($error) {
                return error($error);
            }

            if (!$param) {
                continue;
            }

            $typeName = ReflectionTypeManager::unwrap($reflectionParameter) ?: 'string';

            if ('' === $param->regex()) {
                switch ($typeName) {
                    case 'int':
                        $param->withRegex('[-+]?[0-9]+');
                        break;
                    case 'float':
                        $param->withRegex('[-+]?[0-9]+\.[0-9]+');
                        break;
                    case 'string':
                        $param->withRegex('[^\/]*');
                        break;
                    case 'bool':
                        $param->withRegex('(0|1|no?|y(es)?|false|true)');
                        break;
                }
            }

            $configurations[] = new MatchingPathConfiguration(
                param: $param,
                name: $reflectionParameterName,
                namePattern: '\{'.$reflectionParameterName.'\}',
                isStatic: false,
            );
        }

        $result = [];

        if (preg_match_all('/{([^{}]+)}/', $path, $matches)) {
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
    public function __construct(private readonly array $configurations) {
    }

    public function findParametersFromPath(string $path):PathParametersWrapper {
        return MatchingPathConfiguration::findParametersFromPath($this->configurations, $path);
    }
}
