<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Formularium\Extradata;
use Formularium\ExtradataParameter;
use Formularium\Field;
use Formularium\Model;
use Illuminate\Support\Str;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Modelarium\BaseGenerator;
use Modelarium\Datatypes\Datatype_relationship;
use Modelarium\Datatypes\RelationshipFactory;
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
     * @var string
     */
    protected static $modelDir = 'app/Models/';

    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     * @var \Nette\PhpGenerator\ClassType
     */
    public $class = null;

    /**
     * fillable attributes
     *
     * @var array
     */
    public $fillable = [];

    /**
     * fillable attributes
     *
     * @var array
     */
    public $hidden = [];

    /**
     * cast attributes
     *
     * @var array
     */
    public $casts = [];

    /**
     *
     * @var string
     */
    public $parentClassName = '\Illuminate\Database\Eloquent\Model';

    /**
     * fields
     *
     * @var Model
     */
    public $fModel = null;

    /**
     *
     * @var array
     */
    public $traits = [];

    /**
     * Random generation
     *
     * @var Method
     */
    protected $methodRandom = null;

    /**
     * Do we have a 'can' attribute?
     *
     * @var boolean
     */
    protected $hasCan = true;

    /**
     * If true, we have timestamps on the migration.
     *
     * @var boolean
     */
    protected $migrationTimestamps = false;

    public function generate(): GeneratedCollection
    {
        $this->fModel = Model::create($this->studlyName);
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

        /**
         * @var Field $field
         */
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

        $this->fModel->appendField($field);
    }

    protected function processFieldDirectives(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processModelFieldDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $field,
                    $directive
                );
            }
        }

        // TODO: convert to separate classes
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            
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
            $this->hasCan = true;
            return;
        }

        $generateRandom = false;
        $sourceTypeName = $this->lowerName;
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;

        // TODO: convert to separate classes
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'belongsTo':
                $generateRandom = true;
                $relationship = RelationshipFactory::RELATIONSHIP_ONE_TO_MANY;
                $isInverse = true;
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo')
                    ->setBody("return \$this->belongsTo($targetClass::class);");
                break;

            case 'belongsToMany':
                $generateRandom = true;
                $relationship = RelationshipFactory::RELATIONSHIP_MANY_TO_MANY;
                $isInverse = true;
                $this->class->addMethod($lowerNamePlural)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany')
                    ->setBody("return \$this->belongsToMany($targetClass::class);");
                break;

            case 'hasOne':
                $relationship = RelationshipFactory::RELATIONSHIP_ONE_TO_ONE;
                $isInverse = false;
                $this->class->addMethod($lowerName)
                    ->setPublic()
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\HasOne')
                    ->setBody("return \$this->hasOne($targetClass::class);");
                break;

            case 'hasMany':
                $relationship = RelationshipFactory::RELATIONSHIP_ONE_TO_MANY;
                $isInverse = false;
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
                    $relationship = RelationshipFactory::MORPH_ONE_TO_ONE;
                } else {
                    $relationship = RelationshipFactory::MORPH_ONE_TO_MANY;
                }
                $isInverse = false;

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
                $relationship = RelationshipFactory::MORPH_ONE_TO_MANY; // TODO
                $isInverse = true;
                $this->class->addMethod($field->name)
                    ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo')
                    ->setPublic()
                    ->setBody("return \$this->morphTo();");
                break;

            case 'morphedByMany':
                $relationship = RelationshipFactory::MORPH_MANY_TO_MANY; // TODO
                $isInverse = true;
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

            case 'laravelMediaLibraryData':
                $collection = 'images';
                $customFields = [];
                $studlyFieldName = Str::studly($field->name);

                // deps
                if (!in_array('\\Spatie\\MediaLibrary\\HasMedia', $this->class->getImplements())) {
                    $this->class->addImplement('\\Spatie\\MediaLibrary\\HasMedia');
                    $this->class->addTrait('\\Spatie\\MediaLibrary\\InteractsWithMedia');
                }

                // args
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    switch ($arg->name->value) {
                    case 'collection':
                        /** @phpstan-ignore-next-line */
                        $collection = $arg->value->value;
                    break;
                    case 'fields':
                        /** @phpstan-ignore-next-line */
                        foreach ($arg->value->values as $item) {
                            $customFields[] = $item->value;
                        }
                    break;
                    }
                }
                $studlyCollection = Str::studly($collection);

                // registration
                if (!$this->class->hasMethod("registerMediaCollections")) {
                    $registerMediaCollections = $this->class->addMethod("registerMediaCollections")
                        ->setPublic()
                        ->setReturnType('void')
                        ->addComment("Configures Laravel media-library");
                } else {
                    $registerMediaCollections = $this->class->getMethod("registerMediaCollections");
                }
                $registerMediaCollections->addBody("\$this->addMediaCollection(?);\n", [$collection]);

                // all image models for this collection
                $this->class->addMethod("getMedia{$studlyCollection}Collection")
                    ->setPublic()
                    ->setReturnType('\\Spatie\\MediaLibrary\\MediaCollections\\Models\\Collections\\MediaCollection')
                    ->addComment("Returns a collection media from Laravel-MediaLibrary")
                    ->setBody("return \$this->getMedia(?);", [$collection]);

                // custom fields
                $this->class->addMethod("getMedia{$studlyCollection}CustomFields")
                    ->setPublic()
                    ->setReturnType('array')
                    ->addComment("Returns custom fields for the media")
                    ->setBody("return ?;", [$customFields]);

                $this->class->addMethod("get{$studlyFieldName}urlAttribute")
                    ->setPublic()
                    ->setReturnType('string')
                    ->addComment("Returns the media attribute (url) for the $collection")
                    ->setBody( /** @lang PHP */
                        <<< PHP
        \$image = \$this->getMedia{$studlyCollection}Collection()->first();
        if (\$image) {
            return \$image->getUrl();
        }
        return '';
        PHP
                    );

                // all image models for this collection
                $this->class->addMethod("get{$studlyFieldName}Attribute")
                    ->setPublic()
                    ->setReturnType('array')
                    ->addComment("Returns media attribute for the $collection media with custom fields")
                    ->setBody( /** @lang PHP */
                        <<< PHP
        \$image = \$this->getMedia{$studlyCollection}Collection()->first();
if (\$image) {
    \$customFields = [];
    foreach (\$this->getMedia{$studlyCollection}CustomFields() as \$c) {
        \$customFields[\$c] = \$image->getCustomProperty(\$c);
    }
    return [
        'url' => \$image->getUrl(),
        'fields' => json_encode(\$customFields)
    ];
}
return [];
PHP
                    );
                return;
            
            default:
                break;
            }
        }
        if (!$relationship) {
            throw new Exception("Could not find a relationship in {$typeName} for {$field->name} in {$sourceTypeName}");
        }

        $relationshipDatatype = "relationship:" . ($isInverse ? "inverse:" : "") .
            "$relationship:$sourceTypeName:$targetTypeName";

        $this->processField($relationshipDatatype, $field, $directives, $isRequired);

        if ($generateRandom) {
            if ($relationship == RelationshipFactory::RELATIONSHIP_MANY_TO_MANY || $relationship == RelationshipFactory::MORPH_MANY_TO_MANY) {
                // TODO: do we generate it? seed should do it?
            } else {
                $this->methodRandom->addBody(
                    '$data["' . $lowerName . '_id"] = function () {' . "\n" .
                '    return factory(' . $targetClass . '::class)->create()->id;'  . "\n" .
                '};'
                );
            }
        }
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        // TODO: convert to separate classes
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $this->fModel->appendExtradata(FormulariumUtils::directiveToExtradata($directive));

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
            case 'migrationTimestamps':
                $this->migrationTimestamps = true;
                break;
            case 'modelExtends':
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
                break;
            case 'renderable':
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $argName = $arg->name->value;
                    $argValue = $arg->value->value; /** @phpstan-ignore-line */
                    $this->fModel->appendRenderable($argName, $argValue);
                }
                break;
            }
        }
    }

    public function generateString(): string
    {
        $namespace = new \Nette\PhpGenerator\PhpNamespace('App\\Models');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasOne');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasMany');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphOne');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphToMany');
        $namespace->addUse('\\Illuminate\\Support\\Facades\\Auth');
        $namespace->addUse('\\Formularium\\Exception\\NoRandomException');
        $namespace->addUse('\\Modelarium\\Laravel\\Datatypes\\Datatype_relationship');

        $this->class = $namespace->addClass('Base' . $this->studlyName);
        $this->class
            ->addComment("This file was automatically generated by Modelarium.")
            ->setAbstract();

        $this->methodRandom = new Method('getRandomData');
        $this->methodRandom->addBody(
            '$data = static::getFormularium()->getRandom(get_called_class() . \'::getRandomFieldData\');' . "\n"
        );

        $this->processGraphql();

        // this might have changed
        $this->class->setExtends($this->parentClassName);

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

        if (!$this->migrationTimestamps) {
            $this->class->addProperty('timestamps')
                ->setPublic()
                ->setValue(false)
                ->setComment("Do not set timestamps.\n@var boolean")
                ->setInitialized();
        }

        if ($this->casts) {
            $this->class->addProperty('casts')
                ->setProtected()
                ->setValue($this->casts)
                ->setComment("The attributes that should be cast.\n@var array")
                ->setInitialized();
        }

        $this->class->addMethod('getFields')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addComment('@return array')
            ->addBody(
                "return ?;\n",
                [
                    $this->fModel->serialize()
                ]
            );

        $this->class->addMethod('getFormularium')
            ->setPublic()
            ->setStatic()
            ->setReturnType('\Formularium\Model')
            ->addComment('@return \Formularium\Model')
            ->addBody(
                '$model = \Formularium\Model::fromStruct(static::getFields());' . "\n" .
                'return $model;',
                [
                    //$this->studlyName,
                ]
            );
        
        $this->methodRandom
            ->addComment('@return array')
            ->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addBody('return $data;');
        $this->class->addMember($this->methodRandom);

        $this->class->addMethod('getRandomFieldData')
            ->setPublic()
            ->setStatic()
            ->addComment("Filters fields and generate random data. Throw NoRandomException for fields you don't want to generate random data, or return a valid value.")
            ->addBody('
$d = $f->getDatatype();
if ($d instanceof Datatype_relationship) {
    throw new NoRandomException($f->getName());
}
return $f->getDatatype()->getRandom();')
            ->addParameter('f')->setType('Formularium\Field');

        // TODO perhaps we can use PolicyGenerator->policyClasses to auto generate
        if ($this->hasCan) {
            $this->class->addMethod('getCanAttribute')
                ->setPublic()
                ->setReturnType('array')
                ->addComment("Returns the policy permissions for actions such as editing or deleting.\n@return \Formularium\Model")
                ->addBody(
                    '$policy = new \\App\\Policies\\' . $this->studlyName . 'Policy();' . "\n" .
                    '$user = Auth::user();' . "\n" .
                    'return [' . "\n" .
                    '    //[ "ability" => "create", "value" => $policy->create($user) ]' . "\n" .
                    '];'
                );
        }
        
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
                $this->processFieldDirectives($field, $directives);
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
        return $this->getBasePath(self::$modelDir . '/' . ($base ? 'Base' : '') . $this->studlyName . '.php');
    }

    public static function setModelDir(string $dir): void
    {
        self::$modelDir = $dir;
    }
}
