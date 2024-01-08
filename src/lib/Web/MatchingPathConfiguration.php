<?php

namespace CatPaw\Web;

use CatPaw\Web\Attributes\Param;

readonly class MatchingPathConfiguration {
    /**
     * Find a configuration in an array of configurations by name.
     * @param  array<MatchingPathConfiguration> $configurations
     * @param  MatchingPathConfiguration        $configuration
     * @return bool
     */
    public static function findInArrayByName(array $configurations, MatchingPathConfiguration $configuration):bool {
        if(in_array($configuration->name, $configurations, true)) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param  array<MatchingPathConfiguration> $configurations
     * @param  string                           $path
     * @return array<MatchingPathConfiguration>
     */
    public static function mergeWithPath(array $configurations, string $path):array {
        $length = count($configurations);
        if (0 === $length) {
            $namePattern = preg_quote($path, '/');
            return [
                new MatchingPathConfiguration(
                    param: new Param($namePattern),
                    name: $path,
                    namePattern: $namePattern,
                    isStatic: true,
                ),
            ];
        }

        $regexi = [];
        $names  = [];
        foreach ($configurations as $configuration) {
            $regexi[] = $configuration->namePattern;
            $names[]  = $configuration->name;
        }

        $statics = preg_split('/('.join("|", $regexi).')/', $path);

        $pattern = '/(?<={)('.join('|', $names).')(?=})/';
        preg_match_all($pattern, $path, $matches);
        [$dynamics] = $matches;

        $merged = [];

        foreach ($statics as $key => $static) {
            $namePattern = preg_quote($static, '/');

            $merged[] = new MatchingPathConfiguration(
                param: new Param($namePattern),
                name: $static,
                namePattern: $namePattern,
                isStatic: true,
            );

            if (!isset($dynamics[$key])) {
                continue;
            }

            $dynamic = $dynamics[$key];

            foreach ($configurations as $configuration) {
                if ($configuration->name === $dynamic) {
                    $merged[] = $configuration;
                    break;
                }
            }
        }

        return $merged;
    }

    
    /**
     * 
     * @param  array<MatchingPathConfiguration> $configurations
     * @param  string                           $path
     * @return false|array
     */
    public static function findParametersFromPath(array $configurations, string $path):false|array {
        $pattern  = '';
        $dynamics = [];
        foreach ($configurations as $configuration) {
            if ($configuration->isStatic) {
                $pattern .= $configuration->param->getRegex();
            } else {
                $dynamics[] = $configuration;
                $pattern .= '('.$configuration->param->getRegex().')';
            }
        }

        if (!preg_match("/^$pattern$/", $path, $matches)) {
            return false;
        }

        $result = [];
        $count  = count($matches);

        for ($index = 1; $index < $count; $index++) {
            $key          = $dynamics[$index - 1]->name;
            $result[$key] = $matches[$index];
        }

        return $result;
    }

    /**
     * 
     * @param  Param  $param       the Param attribute attached to the parameter.
     * @param  string $name        name of the parameter.
     * @param  string $namePattern pattern used to detect the parameter in the declared path string.\
     *                             For example a `$namedPattern` of `\{id\}` would match the `id` parameter in the string `/user/{id}`.
     *                             > **WARNING**\
     *                             > This pattern is **NOT** executed at runtime to match a request, that job is delegated to `$param->getRegex()`.
     * @return void
     */
    public function __construct(
        public Param $param,
        public string $name,
        public string $namePattern,
        public bool $isStatic,
    ) {
    }
}