<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Modelarium\BaseGenerator;
use Modelarium\Datatypes\Datatype_relationship;
use Modelarium\Exception\Exception;
use Modelarium\FormulariumUtils;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;
use Modelarium\Types\FormulariumScalarType;
use Nette\PhpGenerator\Method;

class ModelGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

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
                $this->templateStub('model'),
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

        $field = null;
        if (!$scalarType) {
            // probably another model
            $field = FormulariumUtils::getFieldFromDirectives(
                $fieldName,
                $typeName,
                $directives
            );
        } elseif ($scalarType instanceof FormulariumScalarType) {
            $field = FormulariumUtils::getFieldFromDirectives(
                $fieldName,
                $scalarType->getDatatype()->getName(),
                $directives
            );
        } else {
            return;
        }

        if ($isRequired) {
            $field->setValidatorOption(
                Datatype::REQUIRED,
                'value',
                true
            );
        }

        $this->fields[$fieldName] = $field->toArray();
    }

    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);

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

        $typeName = $type->name;
        $this->processField($typeName, $field, $directives, $isRequired);
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $lowerName = mb_strtolower($this->getInflector()->singularize($field->name));
        $lowerNamePlural = $this->getInflector()->pluralize($lowerName);

        $targetClass = '\\App\\Models\\' . Str::studly($this->getInflector()->singularize($field->name));

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name;

        // special types that should be skipped.
        if ($typeName === 'Can') {
            return;
        }

        $generateRandom = false;
        $sourceTypeName = $this->lowerName;
        $targetTypeName = $lowerName;
        $relationship = null;

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'belongsTo':
                $generateRandom = true;
                $relationship = 'N1'; // TODO
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo')
                    ->setBody("return \$this->belongsTo($targetClass::class);");
                break;

            case 'belongsToMany':
                $generateRandom = true;
                $relationship = 'NN'; // TODO
                $this->class->addMethod($lowerNamePlural)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo')
                    ->setBody("return \$this->belongsToMany($targetClass::class);");
                break;

            case 'hasOne':
                $relationship = '11'; // TODO
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\HasOne')
                    ->setBody("return \$this->hasOne($targetClass::class);");
                break;

            case 'hasMany':
                $relationship = '1N'; // TODO, NN?
                $target = $this->getInflector()->singularize($targetClass);
                $this->class->addMethod($lowerNamePlural)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\HasMany')
                    ->setBody("return \$this->hasMany($target::class);");
                break;

            case 'morphOne':
            case 'morphMany':
            case 'morphToMany':
                if ($name === 'morphOne') {
                    $relationship = '11'; // Datatype_relationship::RELATIONSHIP_ONE_TO_ONE; // TODO
                } else {
                    $relationship = 'N1'; // Datatype_relationship::RELATIONSHIP_ONE_TO_MANY; // TODO
                }

                $targetType = $this->parser->getType($typeName);
                if (!$targetType) {
                    throw new Exception("Cannot get type {$typeName} as a relationship to {$this->baseName}");
                } elseif (!($targetType instanceof ObjectType)) {
                    throw new Exception("{$typeName} is not a type for a relationship to {$this->baseName}");
                }
                $targetField = null;
                foreach ($targetType->getFields() as $subField) {
                    $subDir = Parser::getDirectives($subField->astNode->directives);
                    if (array_key_exists('morphTo', $subDir) || array_key_exists('morphedByMany', $subDir)) {
                        $targetField = $subField->name;
                        break;
                    }
                }
                if (!$targetField) {
                    throw new Exception("{$targetType} does not have a '@morphTo' or '@morphToMany' field");
                }

                $this->class->addMethod($field->name)
                    // TODO: return type
                    ->setPublic()
                    ->setBody("return \$this->{$name}($typeName::class, '$targetField');");
                break;
    
            case 'morphTo':
                $relationship = 'N1'; // Datatype_relationship::RELATIONSHIP_ONE_TO_MANY; // TODO
                $this->class->addMethod($field->name)
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo')
                    ->setPublic()
                    ->setBody("return \$this->morphTo();");
                break;

            case 'morphedByMany':
                $relationship = 'NN';// TODO Datatype_relationship::RELATIONSHIP_MANY_TO_MANY; // TODO
                $typeMap = $this->parser->getSchema()->getTypeMap();
       
                foreach ($typeMap as $name => $object) {
                    if (!($object instanceof ObjectType) || $name === 'Query' || $name === 'Mutation' || $name === 'Subscription') {
                        continue;
                    }

                    /**
                     * @var ObjectType $object
                     */

                    if (str_starts_with((string)$name, '__')) {
                        // internal type
                        continue;
                    }

                    foreach ($object->getFields() as $subField) {
                        $subDirectives = Parser::getDirectives($subField->astNode->directives);

                        if (!array_key_exists('morphToMany', $subDirectives)) {
                            continue;
                        }

                        $methodName = $this->getInflector()->pluralize(mb_strtolower((string)$name));
                        $this->class->addMethod($methodName)
                                ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\MorphToMany')
                                ->setPublic()
                                ->setBody("return \$this->morphedByMany($name::class, '$lowerName');");
                    }
                }
                break;
            
            default:
                break;
            }
        }
        if (!$relationship) {
            throw new Exception("Could not find a relationship in {$typeName}");
        }

        $relationshipDatatype = "relationship:$relationship:$sourceTypeName:$targetTypeName";

        $this->processField($relationshipDatatype, $field, $directives, $isRequired);

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
        $namespace = new \Nette\PhpGenerator\PhpNamespace('App\\Models');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasOne');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasMany');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphToMany');
        $namespace->addUse('\\Illuminate\\Support\\Facades\\Auth');

        $this->class = $namespace->addClass('Base' . $this->studlyName);
        $this->class->setExtends($this->parentClassName)
            ->addComment("This file was automatically generated by Modelarium.")
            ->setAbstract();

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

        // TODO perhaps we can use PolicyGenerator->policyClasses to auto generate
        $this->class->addMethod('getCanAttribute')
            ->setPublic()
            ->setReturnType('array')
            ->addComment('@return \Formularium\Model')
            ->addBody(
                '$policy = new \\App\\Policies\\' . $this->studlyName . 'Policy();' . "\n" .
                '$user = Auth::user();' . "\n" .
                'return [' . "\n" .
                '    //[ "ability" => "create", "value" => $policy->create($user) ]' . "\n" .
                '];'
            );
        
        $printer = new \Nette\PhpGenerator\PsrPrinter;
        return $this->phpHeader() . $printer->printNamespace($namespace);
    }

    protected function processGraphql(): void
    {
        foreach ($this->type->getFields() as $field) {
            $directives = $field->astNode->directives;
            if (
                ($field->type instanceof ObjectType) ||
                ($field->type instanceof ListOfType) ||
                ($field->type instanceof UnionType) ||
                ($field->type instanceof NonNull && (
                    ($field->type->getWrappedType() instanceof ObjectType) ||
                    ($field->type->getWrappedType() instanceof ListOfType) ||
                    ($field->type->getWrappedType() instanceof UnionType)
                ))
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
        return $this->getBasePath('app/Models/' . ($base ? 'Base' : '') . $this->studlyName . '.php');
    }
}
