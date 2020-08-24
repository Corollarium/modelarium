<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Modelarium\Datatypes\RelationshipFactory as DatatypesRelationshipFactory;

final class RelationshipFactory extends DatatypesRelationshipFactory
{
    public static function getNamespace(): string
    {
        return __NAMESPACE__;
    }
}
