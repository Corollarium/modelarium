<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Factory\DatatypeFactory;
use Formularium\Factory\RenderableFactory;

// init our magical relationship datatype generator
DatatypeFactory::registerFactory(
    'Modelarium\\Laravel\\Datatypes\\RelationshipFactory::factoryName'
);
RenderableFactory::appendNamespace(
    'Modelarium\\Frontend'
);
