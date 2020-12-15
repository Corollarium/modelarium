<?php declare(strict_types=1);

namespace Modelarium;

final class Modelarium
{
    /**
     * Namespaces for base laravel libraries
     *
     * @var string[]
     */
    protected static $directiveLaravelLibraries = [
        'Modelarium'
    ];

    /**
     * Register a library that provides Laravel directives.
     *
     * @param string $ns
     * @return void
     */
    public static function registerDirectiveLaravelLibrary(string $ns): void
    {
        self::$directiveLaravelLibraries[] = $ns;
    }

    public static function getDirectiveLaravelLibraries(): array
    {
        return self::$directiveLaravelLibraries;
    }

    /**
     * Directive namespaces
     * @return string[] The list of directive namespaces
     */
    public static function getGeneratorDirectiveNamespaces(): array
    {
        return array_map(
            function ($i) {
                return $i . '\\Laravel\\Directives';
            },
            self::$directiveLaravelLibraries
        );
    }

    /**
     * Lighthouse directive namespaces
     *
     * @return string[]
     */
    public static function getGeneratorLighthouseDirectiveNamespaces(): array
    {
        return array_map(
            function ($i) {
                return $i . '\\Laravel\\Lighthouse\\Directives';
            },
            self::$directiveLaravelLibraries
        );
    }
}
