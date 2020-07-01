<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Symfony\Component\VarExporter\VarExporter;

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
     * fields
     *
     * @var array
     */
    protected $fields = [];

    /**
     *
     * @var array
     */
    protected $traits = [];

    public function generate(): GeneratedCollection
    {
        $x = new GeneratedCollection([
            new GeneratedItem(
                GeneratedItem::TYPE_MODEL,
                $this->generateString(),
                $this->getGenerateFilename()
            ),
            new GeneratedItem(
                GeneratedItem::TYPE_MODEL,
                $this->stubToString('model'),
                $this->getGenerateFilename(false),
                true
            )
        ]);
        return $x;
    }

    protected function processField(
        string $typeName,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ) {
        $fieldName = $field->name;
        $scalarType = $this->parser->getScalarType($typeName);
        if ($scalarType) {
            $validators = [];
            $extensions = [];
            foreach ($directives as $directive) {
                $name = $directive->name->value;
                // TODO
            }

            $this->fields[$fieldName] = [
                'name' => $fieldName,
                'type' => $scalarType->name,
                'validators' => $validators,
                'extensions' => $extensions,
            ];
        }
    }

    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;
        $extra = [];

        $isRequired = false;

        if ($field->type instanceof NonNull) {
            $isRequired = true;
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        if ($field->type instanceof ListOfType) {
            $type = $field->type->getWrappedType();
        }

        $validators = [];
        if ($isRequired) {
            $validators = [
                Datatype::REQUIRED => true
            ];
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'fillableAPI':
                $this->fillable[] = $name;
                break;
            case 'hiddenAPI':
                $this->hidden[] = $name;
                break;
            }
        }

        $typeName = $type->name; /** @phpstan-ignore-line */
        switch ($typeName) {
        case 'ID':
        break;
        case 'String':
        case 'Integer':
        case 'Float':
        case 'Boolean':
            $this->fields[$fieldName] = [
                'name' => $fieldName,
                'type' => $typeName,
                'validators' => [],
                'extensions' => [],
            ];
        }
    
        $this->processField($typeName, $field, $directives);
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $lowerName = mb_strtolower($this->inflector->singularize($field->name));
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        $extra = [];
        $targetClass = 'App\\' . Str::studly($this->inflector->singularize($field->name));

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

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        if ($field->type instanceof ListOfType) {
            $type = $field->type->getWrappedType();
        }

        $typeName = $type->name; /** @phpstan-ignore-line */
        if ($typeName) {
            $this->processField($typeName, $field, $directives);
        }

        return $extra;
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'softDeletesDB':
                $this->traits[] = '\Illuminate\Database\Eloquent\SoftDeletes';
                break;
            }
        }
        return [];
    }

    protected function formulariumModel(

    ): string {
        $fields = [];
        foreach ($this->fields as $f) {
            $string = <<<EOF
            new \Formularium\Field(
                '{$f->name}',
                '',
                [ // extensions
                ],
                [ // validators
                ]
            ),
EOF;
        }
        return '';
    }

    public function generateString(): string
    {
        return $this->stubToString('modelbase', function ($stub) {
            $db = [];

            foreach ($this->type->getFields() as $field) {
                // TODO if (NonNull)

                $directives = $field->astNode->directives;
                if (
                    ($field->type instanceof ObjectType) ||
                    ($field->type instanceof ListOfType) ||
                    ($field->type instanceof NonNull) && (
                        ($field->type->getWrappedType() instanceof ObjectType) ||
                        ($field->type->getWrappedType() instanceof ListOfType)
                    )
                ) {
                    // relationship
                    $db = array_merge($db, $this->processRelationship($field, $directives));
                } else {
                    $this->processBasetype($field, $directives);
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
                '{{fieldsCode}}',
                '$fields = ' . VarExporter::export($this->fields) . ';',
                $stub
            );

            $stub = str_replace(
                '{{dummyMethods}}',
                join("\n", $db),
                $stub
            );

            $stub = str_replace(
                '{{dummyFillable}}',
                VarExporter::export($this->fillable),
                $stub
            );

            $stub = str_replace(
                '{{dummyHidden}}',
                VarExporter::export($this->hidden),
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

    public function getGenerateFilename(bool $base = true): string
    {
        return $this->getBasePath('app/' . ($base ? 'Base' : '') . $this->studlyName . '.php');
    }
}
