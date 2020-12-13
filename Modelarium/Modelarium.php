<?php declare(strict_types=1);

namespace Modelarium;

final class Modelarium
{
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
     */
    public static function getGeneratorDirectiveNamespaces()
    {
        return array_map(
            function ($i) {
                return $i . '\\Laravel\\Directives';
            },
            self::$directiveLaravelLibraries
        );
    }

    public static function getGeneratorLighthouseDirectiveNamespaces()
    {
        return array_map(
            function ($i) {
                return $i . '\\Laravel\\Lighthouse\\Directives';
            },
            self::$directiveLaravelLibraries
        );
    }
}
