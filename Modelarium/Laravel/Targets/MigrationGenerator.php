<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\Type;
use Modelarium\Laravel\FieldParameter;
use Modelarium\Laravel\ModelParameter;

class MigrationGenerator extends BaseGenerator
{
    public function generateString(): string
    {
        // TODO: check if a migration '_create_'. $this->lowerName exists, generate a diff from model(), generate new migration with diff
  
        return $this->stubToString('migration', function ($stub) {
            /**
             * @var Type
             */
            $modelData = $this->model->getSchema()->getType($this->targetName);
            assert($modelData !== null);

            $db = [
                '$table->timestamps();'
            ];
            foreach ($modelData->getFields() as $field) {
                // TODO if (NonNull)
                $type = $field->type->getWrappedType();
                $fieldName = $field->name;
                $basetype = $type->name;

                // TODO $basetype = $field->getExtension(FieldParameter::LARAVEL_TYPE, $datatype->getBasetype());
                switch ($basetype) {
                case Type::ID:
                    $db[] = '$table->bigIncrements(\'id\');';
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
                    if ($field->getExtension(FieldParameter::FOREIGN_KEY, false)) {
                        $db[] = '$table->foreign("' . $fieldName . '_id")->references("id")->on("' . $fieldName . '");';
                    }
                break;
                case 'url':
                    $db[] = '$table->string("' . $fieldName . '");';
                break;
                default:
                    $db[] = '$table->' . $basetype . '("' . $fieldName . '");';
                break;
                }
            }
            // TODO: $table->index() $model->getExtension()
            // if ($this->model->getExtension(ModelParameter::SOFT_DELETES, false)) {
            //     $db[] = '$table->softDeletes();';
            // }
            $stub = str_replace(
                '// dummyCode',
                join("\n            ", $db),
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                $modelData->toString(),
                $stub
            );
            return $stub;
        });
    }

    protected function getGenerateFilename(): string
    {
        return $this->getBasePath('database/migrations/' . date('Y_m_d_His') . '_create_'. $this->lowerName . '_table.php');
    }
}
