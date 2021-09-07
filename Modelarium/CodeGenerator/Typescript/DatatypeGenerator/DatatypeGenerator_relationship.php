<?php declare(strict_types=1);

namespace Modelarium\CodeGenerator\Typescript\DatatypeGenerator;

use Formularium\Field;
use Formularium\CodeGenerator\CodeGenerator;
use Formularium\CodeGenerator\DatatypeGenerator;
use Formularium\CodeGenerator\Typescript\CodeGenerator as TypescriptCodeGenerator;
use Modelarium\Datatypes\Datatype_relationship;

class DatatypeGenerator_relationship implements DatatypeGenerator
{
    public function datatypeDeclaration(CodeGenerator $generator)
    {
        return '';
    }

    public function field(CodeGenerator $generator, Field $field)
    {
        /**
         * @var Datatype_relationship
         */
        $dt = $field->getDatatype();

        /**
         * @var TypescriptCodeGenerator $generator
         */
        return $generator->fieldDeclaration($dt->getTarget(), $field->getName());
    }
}
