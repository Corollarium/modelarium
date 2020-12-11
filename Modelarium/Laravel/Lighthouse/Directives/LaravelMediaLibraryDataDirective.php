<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class LaravelMediaLibraryDataDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Implement the Laravel Media Library attributes on a model
"""
directive @laravelMediaLibraryData (
    """
    The collection name to use
    """
    collection: String

    """
    The list of fields to compose in the index
    """
    fields: [String!]
) on FIELD_DEFINITION

SDL;
    }
}
