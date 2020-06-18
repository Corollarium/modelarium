<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\Laravel\FieldParameter;
use Modelarium\Laravel\ModelParameter;

class SeedGenerator extends BaseGenerator
{
    public function generate()
    {
        $path = $this->getBasePath('database/migrations/' . date('Y_m_d_His') . '_create_'. $this->lowerName . '_table.php');

        // TODO: check if a migration '_create_'. $this->lowerName exists, generate a diff from model(), generate new migration with diff

        $this->stubFile($path, 'migration', false, function ($stub) {
            $db = [
                '$table->bigIncrements(\'id\');',
                '$table->timestamps();'
            ];
            foreach ($this->model->getFields() as $field) {
                $datatype = $field->getDatatype();
                $basetype = $field->getExtension(FieldParameter::LARAVEL_TYPE, $datatype->getBasetype());
                switch ($basetype) {
                case 'choice':
                    /**
                     * @var Datatype_choice $datatype
                     */
                    $db[] = '$table->enum("' . $field->getName() . '", ' . print_r($datatype->getChoices(), true) . ');';
                break;
                case 'datetime':
                    $db[] = '$table->dateTime("' . $field->getName() . '");';
                break;
                case 'association':
                    $db[] = '$table->unsignedInteger("' . $field->getName() . '_id");';
                    if ($field->getExtension(FieldParameter::FOREIGN_KEY, false)) {
                        $db[] = '$table->foreign("' . $field->getName() . '_id")->references("id")->on("' . $field->getName() . '");';
                    }
                break;
                case 'url':
                    $db[] = '$table->string("' . $field->getName() . '");';
                break;
                default:
                    $db[] = '$table->' . $basetype . '("' . $field->getName() . '");';
                break;
                }
            }
            // TODO: $table->index() $model->getExtension()
            if ($this->model->getExtension(ModelParameter::SOFT_DELETES, false)) {
                $db[] = '$table->softDeletes();';
            }
            $stub = str_replace(
                '// dummyCode',
                join("\n            ", $db),
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                $this->model->serialize(),
                $stub
            );
            return $stub;
        });
    }
}
