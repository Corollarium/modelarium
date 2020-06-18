<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\Type;
use Modelarium\Laravel\FieldParameter;
use Modelarium\Laravel\ModelParameter;

class MigrationGenerator extends BaseGenerator
{
    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Type\Definition\Type $type,
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $fieldName = $field->name;
        $basetype = $type->name;
        // TODO: scalars

        $db = [];

        switch ($basetype) {
        case Type::ID:
            $db[] = '$table->bigIncrements("id");';
            break;
        case Type::STRING:
            $db[] = '$table->string("' . $fieldName . '");';
            break;
        case Type::INT:
            $db[] = '$table->integer("' . $fieldName . '");';
            break;
        case Type::BOOLEAN:
            $db[] = '$table->bool("' . $fieldName . '");';
            break;
        case Type::FLOAT:
            $db[] = '$table->float("' . $fieldName . '");';
            break;
        case 'choice':
            /**
             * @var Datatype_choice $datatype
             */
            $db[] = '$table->enum("' . $fieldName . '", ' . print_r($datatype->getChoices(), true) . ');';
        break;
        case 'datetime':
            $db[] = '$table->dateTime("' . $fieldName . '");';
        break;
        case 'association':
            $db[] = '$table->unsignedInteger("' . $fieldName . '_id");';
            /*   TODO if ($field->getExtension(FieldParameter::FOREIGN_KEY, false)) {
                $db[] = '$table->foreign("' . $fieldName . '_id")->references("id")->on("' . $fieldName . '");';
            } */
        break;
        case 'url':
            $db[] = '$table->string("' . $fieldName . '");';
        break;
        default:
            // @hasMany
            // @hasOne
            // @foreignKey
            $db[] = '$table->' . $basetype . '("' . $fieldName . '");';
        break;
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'uniqueIndex':
                $db[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'index':
                $db[] = '$table->index("' . $fieldName . '");';
                break;
            }
        }

        return $db;
    }

    public function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $db = [];

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'softDeletes':
                $db[] = '$table->softDeletes();';
                break;
            case 'index':
                $values = $directive->arguments[0]->value->values;
                
                $indexFields = [];
                foreach ($values as $value) {
                    $indexFields[] = $value->value;
                }
                $db[] = '$table->index("' . implode('", "', $indexFields) .'");';
                break;
            case 'spatialIndex':
                $db[] = '$table->spatialIndex("' . $directive->arguments[0]->value->value .'");';
                break;
            default:
            }
        }

        // TODO: $table->index() $model->getExtension()
        // if ($this->model->getExtension(ModelParameter::SOFT_DELETES, false)) {
        //     $db[] = '$table->softDeletes();';
        // }
        
        return $db;
    }

    public function generateString(): string
    {
        return $this->stubToString('migration', function ($stub) {
            /**
             * @var GraphQL\Type\Definition\Type
             */
            $modelData = $this->model->getSchema()->getType($this->targetName);
            assert($modelData !== null);

            $db = [
                '$table->timestamps();'
            ];
            foreach ($modelData->getFields() as $field) {
                // TODO if (NonNull)
                $type = $field->type->getWrappedType();
                $directives = $field->astNode->directives;
                $db = array_merge($db, $this->processBasetype($field, $type, $directives));
            }

            $db = array_merge($db, $this->processDirectives($modelData->astNode->directives));

            $stub = str_replace(
                '// dummyCode',
                join("\n            ", $db),
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                $modelData->toString(), // TODO: ->source
                $stub
            );
            return $stub;
        });
    }

    protected function getGenerateFilename(): string
    {
        // TODO: check if a migration '_create_'. $this->lowerName exists, generate a diff from model(), generate new migration with diff
  
        return $this->getBasePath('database/migrations/' . date('Y_m_d_His') . '_create_'. $this->lowerName . '_table.php');
    }
}
