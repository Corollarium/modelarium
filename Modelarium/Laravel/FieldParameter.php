<?php declare(strict_types=1);

namespace Modelarium\Laravel;

/**
 * These are parameters passed to Renderable through Field::extensions.
 */
class FieldParameter
{
    const LARAVEL_TYPE = 'laravel:type';
    const FOREIGN_KEY = 'foreign';
}
