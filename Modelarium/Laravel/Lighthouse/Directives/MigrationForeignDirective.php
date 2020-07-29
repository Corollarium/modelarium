<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;

class MigrationForeignDirective extends BaseDirective implements DefinedDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Foreign keys
"""
directive @migrationForeign(
    """
    What field it references
    """
    references: String

    """
    What table it references
    """
    on: String

    """
    What to do onDelete
    """
    onDelete: String

    """
    What to do on Update
    """
    onUpdate: String
) on FIELD_DEFINITION
        
SDL;
    }
}
