<?php declare(strict_types=1);

namespace Formularium\CodeGenerator\LaravelEloquent\DatatypeGenerator;

use Formularium\Field;
use Formularium\CodeGenerator\CodeGenerator;
use Formularium\CodeGenerator\LaravelEloquent\CodeGenerator as LaravelEloquentCodeGenerator;
use Formularium\CodeGenerator\LaravelEloquent\LaravelEloquentDatatypeGenerator as LaravelEloquentLaravelEloquentDatatypeGenerator;
use Formularium\DatabaseEnum;
use Formularium\Datatype;

class DatatypeGenerator_relationship extends LaravelEloquentLaravelEloquentDatatypeGenerator
{
    public function field(CodeGenerator $generator, Field $field)
    {
        /**
         * @var LaravelEloquentCodeGenerator $generator
         */
        return "unsignedBigInteger('{$field->getName()}_id')" .
            ($field->getValidatorOption(Datatype::REQUIRED, 'value', false) ? '' : '->nullable()');
        // TODO: index
    }
}
