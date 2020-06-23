<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\Type;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class ModelGenerator extends BaseGenerator
{
    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     * fillable attributes
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * fillable attributes
     *
     * @var array
     */
    protected $hidden = [];

    /**
     *
     * @var string
     */
    protected $parentClassName = 'Model';

    /**
     *
     * @var array
     */
    protected $traits = [];

    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_MODEL,
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

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'fillable':
                $this->fillable[] = $name;
                break;
            case 'hidden':
                $this->hidden[] = $name;
                break;
            }
        }

        return $extra;
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $lowerName = mb_strtolower($this->inflector->singularize($field->name));
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        $extra = [];
        $targetClass = 'App\\\\' . Str::studly($this->inflector->singularize($field->name));

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'belongsTo':
                $extra[] = <<<EOF
    public function $lowerName()
    {
        return \$this->belongsTo($targetClass::class);
    }
EOF;
                break;

            case 'belongsToMany':
                $extra[] = <<<EOF
    public function $lowerNamePlural()
    {
        return \$this->belongsToMany($targetClass::class);
    }
EOF;
                break;
    
            case 'hasOne':
                $extra[] = <<<EOF
    public function $lowerName()
    {
        return \$this->hasOne($targetClass::class);
    }
EOF;
                break;
            case 'hasMany':
                $targetClass = $this->inflector->singularize($targetClass);
                $extra[] = <<<EOF
    public function $lowerNamePlural()
    {
        return \$this->hasMany($targetClass::class);
    }
EOF;
                break;
            default:
                break;
            }
        }

        return $extra;
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'softDeletes':
                $this->traits[] = 'SoftDeletes';
                break;
            }
        }
        return [];
    }

    public function generateString(): string
    {
        return $this->stubToString('model', function ($stub) {
            $db = [];

            foreach ($this->type->getFields() as $field) {
                // TODO if (NonNull)

                $directives = $field->astNode->directives;
                if (
                    ($field->type instanceof ObjectType) ||
                    ($field->type instanceof NonNull) && (
                        ($field->type->getWrappedType() instanceof ObjectType) ||
                        ($field->type->getWrappedType() instanceof ListOfType)
                    )
                ) {
                    // relationship
                    $db = array_merge($db, $this->processRelationship($field, $directives));
                } else {
                    // $db = array_merge($db, $this->processBasetype($field, $directives));
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
                '{{traitsCode}}',
                $this->traits ? 'use ' . join(', ', $this->traits) . ';' : '',
                $stub
            );

            $stub = str_replace(
                '{{dummyMethods}}',
                join("\n            ", $db),
                $stub
            );

            $stub = str_replace(
                '{{dummyFillable}}',
                var_export($this->fillable, true),
                $stub
            );

            $stub = str_replace(
                '{{dummyHidden}}',
                var_export($this->hidden, true),
                $stub
            );

            $stub = str_replace(
                '{{ParentDummyModel}}',
                $this->parentClassName,
                $stub
            );

            return $stub;
        });
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('app/Models/Base'. $this->studlyName . '.php');
    }
}
