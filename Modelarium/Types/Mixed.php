<?php

declare(strict_types=1);

namespace Modelarium\Types;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\AST;

/**
 * From https://github.com/mll-lab/graphql-php-scalars/blob/master/src/Mixed.php
 */
class Mixed extends ScalarType
{
    /**
     * The description that is used for schema introspection.
     *
     * @var string
     */
    public $description = <<<'DESCRIPTION'
Loose type that allows any value. Be careful when passing in large `Int` or `Float` literals,
as they may not be parsed correctly on the server side. Use `String` literals if you are
dealing with really large numbers to be on the safe side.
DESCRIPTION;

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        return AST::valueFromASTUntyped($valueNode);
    }
}
