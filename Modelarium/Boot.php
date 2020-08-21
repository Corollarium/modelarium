<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Factory\DatatypeFactory;
use Formularium\Formularium;

// TODO: Laravel??
// init our magical relationship datatype generator
DatatypeFactory::registerFactory(
    'Modelarium\\Laravel\\Datatypes\\Datatype_relationship::factoryName'
);
