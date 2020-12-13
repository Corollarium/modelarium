<?php declare(strict_types=1);

namespace Modelarium;

final class Modelarium
{
    protected static $generatorDirectiveNamespaces = [
        'Modelarium\\Laravel\\Directives'
    ];

    /**
     * Register a directive namespace for generator
     *
     * @param string $ns
     * @return void
     */
    public static function registerGeneratorDirectiveNamespace(string $ns): void
    {
        self::$generatorDirectiveNamespaces[] = $ns;
    }

    /**
     * Get the value of directiveNamespaces
     */
    public static function getGeneratorDirectiveNamespaces()
    {
        return self::$generatorDirectiveNamespaces;
    }
}
