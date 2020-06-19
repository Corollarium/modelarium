<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\FieldParameter;
use Modelarium\Laravel\ModelParameter;

class MigrationGenerator extends BaseGenerator
{
    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_MIGRATION,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
    }

    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $fieldName = $field->name;
        $extra = [];

        // TODO: scalars

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }
        $basetype = $type->name;

        switch ($basetype) {
        case Type::ID:
            $base = '$table->bigIncrements("id")';
            break;
        case Type::STRING:
            $base = '$table->string("' . $fieldName . '")';
            break;
        case Type::INT:
            $base = '$table->integer("' . $fieldName . '")';
            break;
        case Type::BOOLEAN:
            $base = '$table->bool("' . $fieldName . '")';
            break;
        case Type::FLOAT:
            $base = '$table->float("' . $fieldName . '")';
            break;
        case 'choice':
            /**
             * @var Datatype_choice $datatype
             */
            $base = '$table->enum("' . $fieldName . '", ' . print_r($datatype->getChoices(), true) . ')';
        break;
        case 'datetime':
            $base = '$table->dateTime("' . $fieldName . '")';
        break;
        case 'url':
            $base = '$table->string("' . $fieldName . '")';
        break;
        default:
            $base = '$table->' . $basetype . '("' . $fieldName . '")';
        break;
        }
        
        if (!($field->type instanceof NonNull)) {
            $base .= '->nullable()';
        }
        
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'uniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'index':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'unsigned':
                $base .= '->unsigned()';
                break;
            case 'defaultValue':
                $x = ''; // TODO
                $base .= '->default(' . $x . ')';
                break;
            }
        }
        $base .= ';';

        array_unshift($extra, $base);
        return $extra;
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Type\Definition\Type $type,
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $lowerName = mb_strtolower($field->name);
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        $fieldName = mb_strtolower($lowerName) . '_id';
        
        $extra = [];
        $base = '$table->unsignedBigInteger("' . $fieldName . '")';

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'uniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'index':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'foreign':
                $extra[] = '$table->foreign("' . $fieldName . '")->references("id")->on("' . $lowerNamePlural . '");';
                break;
            }
        }

        $base .= ';';

        array_unshift($extra, $base);
        return $extra;
    }

    protected function processDirectives(
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
            case 'rememberToken':
                $db[] = '$table->rememberToken();';
                break;
            case 'timestamps':
                $db[] = '$table->timestamps();';
                break;
            default:
            }
        }

        return $db;
    }

    public function generateString(): string
    {
        return $this->stubToString('migration', function ($stub) {
            $db = [];

            foreach ($this->type->getFields() as $field) {
                // TODO if (NonNull)

                $directives = $field->astNode->directives;
                if ($field->type instanceof ObjectType) {
                    // relationship
                    $db = array_merge($db, $this->processRelationship($field, $field->type, $directives));
                } else {
                    $db = array_merge($db, $this->processBasetype($field, $directives, true));
                }
            }

            $db = array_merge($db, $this->processDirectives($this->type->astNode->directives));

            $stub = str_replace(
                '// dummyCode',
                join("\n            ", $db),
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                $this->type->toString(), // TODO: ->source
                $stub
            );
            return $stub;
        });
    }

    public function getGenerateFilename(): string
    {
        // TODO: check if a migration '_create_'. $this->lowerName exists, generate a diff from model(), generate new migration with diff
  
        return $this->getBasePath('database/migrations/' . date('Y_m_d_His') . '_create_'. $this->lowerName . '_table.php');
    }
}
