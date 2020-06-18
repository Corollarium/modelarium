<?php declare(strict_types=1);

namespace App\Formularium\Datatype;

use FormulariumLaravel\Datatype_relationship as Datatype_relationship;

class Datatype_assocname extends Datatype_relationship
{
    public function __construct(string $typename = 'assocname', string $basetype = 'relationship')
    {
        parent::__construct($typename, $basetype);
        $this->relationship = 'RelationshipMode';
        $this->sourceClass = '\\App\\DummyName';
        $this->targetClass = '\\App\\TargetName';
    }
}
