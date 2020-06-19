<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class MigrationGenerator extends BaseGenerator
{
    /**
     * @var ObjectType
     */
    protected $type = null;

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
        $basetype = $type->name; /** @phpstan-ignore-line */

        $base = '';
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
        case 'datetime':
            $base = '$table->dateTime("' . $fieldName . '")';
        break;
        case 'url':
            $base = '$table->string("' . $fieldName . '")';
        break;
        default:
            throw new Exception("Unsupported type " . $basetype);
            // $base = '$table->' . $basetype . '("' . $fieldName . '")';
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
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $lowerName = mb_strtolower($field->name);
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }
        $typeName = $type->name; /** @phpstan-ignore-line */

        $fieldName = $lowerName . '_id';
        
        $base = null;
        $extra = [];

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'uniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'index':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'belongsTo':
                $targetType = $this->parser->getType($typeName);
                if (!$targetType) {
                    throw new Exception("Cannot get type {$typeName} as a relationship to {$this->name}");
                } elseif (!($targetType instanceof ObjectType)) {
                    throw new Exception("{$typeName} is not a type for a relationship to {$this->name}");
                }
                $targetField = $targetType->getField($this->lowerName); // TODO: might have another name than lowerName
                $targetDirectives = $targetField->astNode->directives;
                foreach ($targetDirectives as $targetDirective) {
                    switch ($targetDirective->name->value) {
                    case 'hasOne':
                    case 'hasMany':
                        $base = '$table->unsignedBigInteger("' . $fieldName . '")';
                    break;
                    }
                }
                break;
            case 'foreign':
                $references = 'id';
                $on = $lowerNamePlural;
                $onDelete = null;
                $onUpdate = null;
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $value = $arg->value->value;

                    switch ($arg->name->value) {
                    case 'references':
                        $references = $value;
                    break;
                    case 'on':
                        $on = $value;
                    break;
                    case 'onDelete':
                        $onDelete = $value;
                    break;
                    case 'onUpdate':
                        $onUpdate = $value;
                    break;
                    }
                }
                $extra[] = '$table->foreign("' . $fieldName . '")' .
                    "->references(\"$references\")" .
                    "->on(\"$on\")" .
                    ($onDelete ? "->onDelete(\"$onDelete\")" : '') .
                    ($onUpdate ? "->onUpdate(\"$onUpdate\")" : '') .
                    ';';
                break;
            }
        }

        if ($base) {
            $base .= ';';
            array_unshift($extra, $base);
        }

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
                if (
                    $field->type instanceof ObjectType ||
                    ($field->type instanceof NonNull && $field->type->getWrappedType() instanceof ObjectType)
                ) {
                    // relationship
                    $db = array_merge($db, $this->processRelationship($field, $directives));
                } else {
                    $db = array_merge($db, $this->processBasetype($field, $directives));
                }
            }

            /**
             * @var \GraphQL\Language\AST\NodeList|null
             */
            $directives = $this->type->astNode->directives;
            if ($directives) {
                $db = array_merge($db, $this->processDirectives($directives));
            }

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
