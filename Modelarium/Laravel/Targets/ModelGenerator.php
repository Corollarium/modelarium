<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Types\FormulariumScalarType;
use Nette\PhpGenerator\Method;

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

    /**
     * cast attributes
     *
     * @var Method
     */
    protected $methodRandom = null;

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
        \GraphQL\Language\AST\NodeList $directives,
        bool $isRequired
    ): void {
        $fieldName = $field->name;

        if ($typeName === 'ID') {
            return;
        }

        $scalarType = $this->parser->getScalarType($typeName);

        if ($scalarType) {
            if ($scalarType instanceof FormulariumScalarType) {
                $field = $scalarType->processDirectives(
                    $fieldName,
                    $directives
                );
                
                if ($isRequired) {
                    $field->setValidatorOption(
                        Datatype::REQUIRED,
                        'value',
                        true
                    );
                }
                $this->fields[$fieldName] = $field->toArray();
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

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'modelFillable':
                $this->fillable[] = $fieldName;
                break;
            case 'modelHidden':
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
        $this->processField($typeName, $field, $directives, $isRequired);
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $lowerName = mb_strtolower($this->inflector->singularize($field->name));
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        $targetClass = '\\App\\' . Str::studly($this->inflector->singularize($field->name));

        $generateRandom = false;
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'belongsTo':
                $generateRandom = true;
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setBody("return \$this->belongsTo($targetClass::class);");
                break;

            case 'belongsToMany':
                $generateRandom = true;
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

        $isRequired = false;
        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
            $isRequired = true;
        } else {
            $type = $field->type;
        }

        if ($field->type instanceof ListOfType) {
            $type = $field->type->getWrappedType();
            // TODO: NonNull check again?
        }

        // TODO: relationship $this->processField($typeName, $field, $directives, $isRequired);

        if ($generateRandom) {
            $this->methodRandom->addBody(
                '$data["' . $lowerName . '_id"] = function () {' . "\n" .
                '    return factory(' . $targetClass . '::class)->create()->id;'  . "\n" .
                '};'
            );
        }
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'migrationSoftDeletes':
                $this->traits[] = '\Illuminate\Database\Eloquent\SoftDeletes';
                break;
            case 'modelNotifiable':
                $this->traits[] = '\Illuminate\Notifications\Notifiable';
                break;
            case 'modelMustVerifyEmail':
                $this->traits[] = '\Illuminate\Notifications\MustVerifyEmail';
                break;
            case 'migrationRememberToken':
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
                [ // renderable
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

        $this->methodRandom = new Method('getRandomData');
        $this->methodRandom->addBody(
            '$data = static::getFormularium()->getRandom();' . "\n"
        );

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

        $this->class->addMethod('getFields')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addComment('@return array')
            ->addBody(
                "return ?;\n",
                [
                    $this->fields
                ]
            );

        $this->class->addMethod('getFormularium')
            ->setPublic()
            ->setStatic()
            ->setReturnType('\Formularium\Model')
            ->addComment('@return \Formularium\Model')
            ->addBody(
                '$model = \Formularium\Model::create(?, static::getFields());' . "\n" .
                'return $model;',
                [
                    $this->studlyName,
                ]
            );
        
        $this->methodRandom
            ->addComment('@return array')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addBody('return $data;');
        $this->class->addMember($this->methodRandom);

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
