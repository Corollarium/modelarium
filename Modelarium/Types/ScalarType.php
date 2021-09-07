<?php declare(strict_types=1);

namespace Modelarium\Types;

use Formularium\Datatype;
use GraphQL\Type\Definition\CustomScalarType as GraphQLScalarType;

abstract class ScalarType extends GraphQLScalarType
{
    abstract public function getDatatype(): Datatype;
}
