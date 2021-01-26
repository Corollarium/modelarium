<?php

namespace Modelarium\Laravel\Lighthouse\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;
use Nuwave\Lighthouse\Support\Contracts\r;

class HiddenDirective extends BaseDirective implements FieldManipulator
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
"""
Make this field hidden. It will not show up on introspection or queries.
"""
directive @hidden on FIELD_DEFINITION
GRAPHQL;
    }

    /**
     * Manipulate the AST based on a field definition.
     *
     * @return void
     */
    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType
    ) {
        $fieldName = $fieldDefinition->name->value;
        /**
         * @var NodeList<FieldDefinitionNode> $fields
         */
        $fields = $parentType->fields;
        foreach ($fields as $k => $f) {
            if ($f->name->value === $fieldName) {
                unset($fields[$k]);
            }
        }
    }
}
