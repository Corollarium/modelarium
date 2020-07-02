<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Formularium\Exception\ClassNotFoundException;
use Formularium\Formularium;
use Formularium\Validator;
use Formularium\ValidatorMetadata;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Types\FormulariumScalarType;
use Symfony\Component\VarExporter\VarExporter;

class ModelGenerator extends BaseGenerator
{
    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     * @var \Nette\PhpGenerator\ClassType
     */
    protected $class = null;

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
     * cast attributes
     *
     * @var array
     */
    protected $casts = [];

    /**
     *
     * @var string
     */
    protected $parentClassName = '\Illuminate\Database\Eloquent\Model';

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
    ): void {
        $fieldName = $field->name;

        if ($typeName === 'ID') {
            return;
        }

        $scalarType = $this->parser->getScalarType($typeName);

        if ($scalarType) {
            if ($scalarType instanceof FormulariumScalarType) {
                $this->fields[$fieldName] = $scalarType->processDirectives(
                    $fieldName,
                    $directives
                )->toString();
            }
        }
    }

    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;

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
                $this->fillable[] = $fieldName;
                break;
            case 'hiddenAPI':
                $this->hidden[] = $fieldName;
                break;
            case 'casts':
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $value = $arg->value->value;

                    switch ($arg->name->value) {
                    case 'type':
                        $this->casts[$fieldName] = $value;
                    }
                }
                break;
            }
        }

        $typeName = $type->name; /** @phpstan-ignore-line */
        $this->processField($typeName, $field, $directives);
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $lowerName = mb_strtolower($this->inflector->singularize($field->name));
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        $targetClass = 'App\\' . Str::studly($this->inflector->singularize($field->name));

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'belongsTo':
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setBody("return \$this->belongsTo($targetClass::class);");
                break;

            case 'belongsToMany':
                $this->class->addMethod($lowerNamePlural)
                    ->setPublic()
                    ->setBody("return \$this->belongsToMany($targetClass::class);");
                break;

            case 'hasOne':
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setBody("return \$this->hasOne($targetClass::class);");
                break;

            case 'hasMany':
                $target = $this->inflector->singularize($targetClass);
                $this->class->addMethod($lowerNamePlural)
                    ->setPublic()
                    ->setBody("return \$this->hasMany($target::class);");
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
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'softDeletesDB':
                $this->traits[] = '\Illuminate\Database\Eloquent\SoftDeletes';
                break;
            case 'notifiable':
                $this->traits[] = '\Illuminate\Notifications\Notifiable';
                break;
            case 'mustVerifyEmail':
                $this->traits[] = '\Illuminate\Notifications\MustVerifyEmail';
                break;
            case 'rememberToken':
                $this->hidden[] = 'remember_token';
                break;
            case 'extends':
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $value = $arg->value->value;

                    switch ($arg->name->value) {
                    case 'class':
                        $this->parentClassName = $value;
                    }
                }
            }
        }
    }

    protected function formulariumModel(

    ): string {
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
        $namespace = new \Nette\PhpGenerator\PhpNamespace('App');

        $this->class = $namespace->addClass('Base' . $this->studlyName);
        $this->class->setExtends($this->parentClassName)
            ->addComment("This file was automatically generated by Modelarium.");

        $this->processGraphql();

        foreach ($this->traits as $trait) {
            $this->class->addTrait($trait);
        }

        $this->class->addProperty('fillable')
            ->setProtected()
            ->setValue($this->fillable)
            ->setComment("The attributes that are mass assignable.\n@var array")
            ->setInitialized();

        $this->class->addProperty('hidden')
            ->setProtected()
            ->setValue($this->hidden)
            ->setComment("The attributes that should be hidden for arrays.\n@var array")
            ->setInitialized();

        $this->class->addProperty('casts')
            ->setProtected()
            ->setValue($this->casts)
            ->setComment("The attributes that should be cast to native types.\n@var array")
            ->setInitialized();

        $this->class->addMethod('getFormularium')
            ->setPublic()
            ->setStatic()
            ->setReturnType('\Formularium\Model')
            ->addComment('@return \Formularium\Model')
            ->addBody(
                '$fields = ?;' . "\n" .
                '$model = \Formularium\Model::create(?, $fields);' . "\n" .
                'return $model;',
                [
                    $this->fields,
                    $this->studlyName,
                ]
            );

        $this->class->addMethod('getRandomData')
            ->addComment('@return array')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addBody('return static::getFormularium()->getRandom();');

        $printer = new \Nette\PhpGenerator\PsrPrinter;
        return "<?php declare(strict_types=1);\n\n" . $printer->printNamespace($namespace);
    }

    protected function processGraphql(): void
    {
        foreach ($this->type->getFields() as $field) {
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
                $this->processRelationship($field, $directives);
            } else {
                $this->processBasetype($field, $directives);
            }
        }

        /**
         * @var \GraphQL\Language\AST\NodeList|null
         */
        $directives = $this->type->astNode->directives;
        if ($directives) {
            $this->processDirectives($directives);
        }
    }

    public function getGenerateFilename(bool $base = true): string
    {
        return $this->getBasePath('app/' . ($base ? 'Base' : '') . $this->studlyName . '.php');
    }
}
