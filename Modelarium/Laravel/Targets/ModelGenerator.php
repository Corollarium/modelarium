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
    public $migrationTimestamps = false;

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

        $relationshipDatatype = null;

        $generateRandom = false;
        $sourceTypeName = $this->lowerName;
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;

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

                $methodName = "$className::processModelRelationshipDirective";
                /** @phpstan-ignore-next-line */
                $r = $methodName(
                    $this,
                    $field,
                    $directive
                );
                if ($r) {
                    if ($relationshipDatatype) {
                        throw new Exception("Overwrting relationship in {$typeName} for {$field->name} in {$this->lowerName}");
                    }
                    $relationshipDatatype = $r;
                }
                continue;
            }
        }

        if (!$relationshipDatatype) {
            if (!$relationship) {
                // TODO: generate a warning, perhaps?
                // throw new Exception("Could not find a relationship in {$typeName} for {$field->name} in {$sourceTypeName}");
                return;
            }
    
            $relationshipDatatype = "relationship:" . ($isInverse ? "inverse:" : "") .
               "$relationship:$sourceTypeName:$targetTypeName";
        }

        $this->processField($relationshipDatatype, $field, $directives, $isRequired);

        // TODO
        // if ($generateRandom) {
        //     if ($relationship == RelationshipFactory::RELATIONSHIP_MANY_TO_MANY || $relationship == RelationshipFactory::MORPH_MANY_TO_MANY) {
        //         // TODO: do we generate it? seed should do it?
        //     } else {
        //         $this->methodRandom->addBody(
        //             '$data["' . $lowerName . '_id"] = function () {' . "\n" .
        //         '    return factory(' . $targetClass . '::class)->create()->id;'  . "\n" .
        //         '};'
        //         );
        //     }
        // }
    }

    public static function getRelationshipDatatypeName(
        string $relationship,
        bool $isInverse,
        string $sourceTypeName,
        string $targetTypeName
    ): string {
        return "relationship:" . ($isInverse ? "inverse:" : "") .
            "$relationship:$sourceTypeName:$targetTypeName";
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $this->fModel->appendExtradata(FormulariumUtils::directiveToExtradata($directive));

            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processModelTypeDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $directive
                );
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
