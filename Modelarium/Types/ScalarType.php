<?php declare(strict_types=1);

namespace Modelarium\Types;

use GraphQL\Type\Definition\CustomScalarType as GraphQLScalarType;

abstract class ScalarType extends GraphQLScalarType
{
    /**
     * Returns the suggested SQL type for this datatype, such as 'TEXT'.
     *
     * @param string $database The database
     * @return string
     */
    abstract public function getSQLType(string $database = '', array $options = []): string;

    /**
     * Returns the suggested Laravel Database type for this datatype.
     *
     * @return string
     */
    abstract public function getLaravelSQLType(string $name, array $options = []): string;
}
