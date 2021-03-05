<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class RenderableDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Generate renderable
"""
directive @renderable (
    """Label for this field"""
    label: String

    """Comment for this field"""
    comment: String

    """Should this field be used in show pages?"""
    show: Boolean

    """Is this field the title field for this object?"""
    title: Boolean
    
    """Should this field be used in the form? Default is true"""
    form: Boolean
    
    """Should this field be used in card components?"""
    card: Boolean

    """Should this field be used in table components?"""
    table: Boolean

    """Field size in render"""
    size: String

    # move to schemaRenderable()
    itemtype: String

    # move to typeRenderable()
    routeBase: String
    keyAttribute: String
    name: String
) on FIELD_DEFINITION | OBJECT
SDL;
    }
}
