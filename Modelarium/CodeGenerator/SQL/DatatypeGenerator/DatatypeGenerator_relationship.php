<?php declare(strict_types=1);

namespace Modelarium\CodeGenerator\SQL\DatatypeGenerator;

use Formularium\Field;
use Formularium\CodeGenerator\CodeGenerator;
use Formularium\CodeGenerator\DatatypeGenerator;
use Formularium\CodeGenerator\SQL\SQLDatatypeGenerator;
use Formularium\CodeGenerator\SQL\CodeGenerator as SQLCodeGenerator;
use Formularium\Datatype;

class DatatypeGenerator_integer extends SQLDatatypeGenerator
{
    public function field(CodeGenerator $generator, Field $field)
    {
        /**
         * @var SQLCodeGenerator $generator
         */
        return $this->getSQL(
            $field->getName(),
            'BIGINT',
            $field->getValidatorOption(Datatype::REQUIRED, 'value', false)
        );
    }
}
