<?php declare(strict_types=1);

namespace Modelarium\Laravel;

final class Util
{
    /**
     * Returns the Laravel version we're using.
     *
     * @return string
     */
    public static function getLaravelVersion(): string
    {
        $laravelVersion = '7.x'; // default
        if (function_exists('app')) {
            // @phpstan-ignore-next-line
            $laravelVersion = app()->version();
        }
        return $laravelVersion;
    }
}
