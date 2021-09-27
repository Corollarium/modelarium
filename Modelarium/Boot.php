<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Factory\AbstractFactory;
use Formularium\Factory\DatatypeFactory;

// init our magical relationship datatype generator
DatatypeFactory::registerFactory(
    'Modelarium\\Laravel\\Datatypes\\RelationshipFactory::factoryName'
);
AbstractFactory::appendBaseNamespace(
    'Modelarium'
);
