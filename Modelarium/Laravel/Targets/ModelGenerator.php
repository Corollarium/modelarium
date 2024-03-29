<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype;
use Formularium\Extradata;
use Formularium\ExtradataParameter;
use Formularium\Field;
use Formularium\Model;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Language\AST\DirectiveNode;
use Modelarium\BaseGenerator;
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
     * traits to include
     * @var array
     */
    public $traits = [];

    /**
     * Eager loading
     *
     * @var string[]
     */
    public $with = [];

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
    protected $hasCan = false;

    /**
     * If true, we have timestamps on the migration.
     *
     * @var boolean
     */
    public $migrationTimestamps = false;

    /**
     * Undocumented variable
     *
     * @var GeneratedCollection
     */
    public $generatedCollection = null;

    public function generate(): GeneratedCollection
    {
        $this->generatedCollection = new GeneratedCollection();
        $this->fModel = Model::create($this->studlyName);
        $this->generatedCollection->push(new GeneratedItem(
            GeneratedItem::TYPE_MODEL,
            $this->generateString(),
            $this->getGenerateFilename()
        ));
        $this->generatedCollection->push(new GeneratedItem(
            GeneratedItem::TYPE_MODEL,
            $this->templateStub('model'),
            $this->getGenerateFilename(false),
            true
        ));
        return $this->generatedCollection;
    }

    /**
     * Override to insert extradata
     *
     * @param \GraphQL\Language\AST\NodeList<\GraphQL\Language\AST\DirectiveNode> $directives
     * @param string $generatorType
     * @return void
     */
    protected function processTypeDirectives(
        \GraphQL\Language\AST\NodeList $directives,
        string $generatorType
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $this->fModel->appendExtradata(FormulariumUtils::directiveToExtradata($directive));
    
            $className = $this->getDirectiveClass($name, $generatorType);
            if ($className) {
                $methodName = "$className::process{$generatorType}TypeDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $directive
                );
            }
        }
    }

    /**
     * @param string $typeName
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\NodeList<DirectiveNode> $directives
     * @param boolean $isRequired
     * @return void
     */
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
         * @var Field $fieldFormularium
         */
        $fieldFormularium = null;
        if (!$scalarType) {
            // probably another model
            $fieldFormularium = FormulariumUtils::getFieldFromDirectives(
                $fieldName,
                $typeName,
                $directives
            );
        } elseif ($scalarType instanceof FormulariumScalarType) {
            $fieldFormularium = FormulariumUtils::getFieldFromDirectives(
                $fieldName,
                $scalarType->getDatatype()->getName(),
                $directives
            );
        } else {
            return;
        }

        if ($isRequired) {
            $fieldFormularium->setValidatorOption(
                Datatype::REQUIRED,
                'value',
                true
            );
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processModelFieldDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $field,
                    $fieldFormularium,
                    $directive
                );
            }
        }

        $this->fModel->appendField($fieldFormularium);
    }

    /**
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\NodeList<DirectiveNode> $directives
     * @return void
     */
    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        list($type, $isRequired) = Parser::getUnwrappedType($field->getType());
        $typeName = $type->name;

        // special types that should be skipped.
        if ($typeName === 'Can') {
            $this->hasCan = true;
            $this->fModel->appendExtradata(
                new Extradata(
                    'hasCan',
                    [ new ExtradataParameter('value', true) ]
                )
            );
            return;
        }

        $relationshipDatatype = null;

        foreach ($directives as $directive) {
            $name = $directive->name->value;

            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processModelRelationshipDirective";
                /** @phpstan-ignore-next-line */
                $r = $methodName(
                    $this,
                    $field,
                    $directive,
                    $relationshipDatatype
                );
                if ($r) {
                    if ($relationshipDatatype) {
                        throw new Exception("Overwriting relationship in {$typeName} for {$field->name} in {$this->baseName}");
                    }
                    $relationshipDatatype = $r;
                }
                continue;
            }
        }

        if (!$relationshipDatatype) {
            // if target is a model...
            $targetType = $this->parser->getSchema()->getType($typeName);
            /** @phpstan-ignore-next-line */
            $directives = $targetType->astNode->directives;
            $skip = false;
            foreach ($directives as $directive) {
                $dName = $directive->name->value;
                if ($dName === 'typeSkip') {
                    $skip = true;
                    break;
                }
            }
            if ($skip == false) {
                $this->warn("Could not find a relationship {$typeName} for {$field->name} in {$this->baseName}. Consider adding a @modelAccessor or declaring the relationship (e.g. @hasMany, @belongsTo).");
            }
            return;
        }
    
        $this->processField($relationshipDatatype->getName(), $field, $directives, $isRequired);

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

    public function generateString(): string
    {
        $namespace = new \Nette\PhpGenerator\PhpNamespace('App\\Models');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasOne');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\HasMany');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphOne');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Relations\\MorphToMany');
        $namespace->addUse('\\Illuminate\\Database\\Eloquent\\Builder');
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

        $this->class->addProperty('with')
            ->setProtected()
            ->setValue($this->with)
            ->setComment("Eager load these relationships.\n@var array")
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

        $getRandomFieldData = $this->class->addMethod('getRandomFieldData')
            ->setPublic()
            ->setStatic()
            ->addComment("Filters fields and generate random data. Throw NoRandomException for fields you don't want to generate random data, or return a valid value.")
            ->addBody('
$d = $field->getDatatype();
if ($field->getExtradata("migrationSkip")) {
    throw new NoRandomException($field->getName());
}
if ($d instanceof Datatype_relationship) {
    if (!$d->getIsInverse() || !$field->getValidatorOption("required", "value", false)) {
        throw new NoRandomException($field->getName());
    }
    $data[$field->getName() . "_id"] = $field->getDatatype()->getRandom();
} else {
    $data[$field->getName()] = $field->getDatatype()->getRandom();
}');
        $getRandomFieldData->addParameter('field')->setType('Formularium\Field');
        $getRandomFieldData->addParameter('model')->setType('Formularium\Model');
        $getRandomFieldData->addParameter('data')->setType('array')->setReference(true);

        // TODO perhaps we can use PolicyGenerator->policyClasses to auto generate

        if ($this->hasCan) {
            $this->class->addMethod('getCanAttribute')
                ->setPublic()
                ->setReturnType('array')
                ->addComment("Returns the policy permissions for actions such as editing or deleting.\n@return array")
                ->addBody(
                    '$policy = new \\App\\Policies\\' . $this->studlyName . 'Policy();' . "\n" .
                    '$user = Auth::user();' . "\n" .
                    'return [' . "\n" .
                    '    //[ "ability" => "create", "value" => $policy->create($user) ]' . "\n" .
                    '];'
                );

            /*  This creates a policy, but it's not useful. It's an empty file and @can won't patch it for now
            if (!class_exists('\\App\\Policies\\' . $this->studlyName . 'Policy')) {
                $policyGenerator = new PolicyGenerator($this->parser, 'Mutation', $this->type);
                $z = $policyGenerator->getPolicyClass($this->studlyName);
                $x = $policyGenerator->generate();
                $this->generatedCollection = $this->generatedCollection->merge($x);
            }
            */
        }
        
        $printer = new \Nette\PhpGenerator\PsrPrinter;
        return $this->phpHeader() . $printer->printNamespace($namespace);
    }

    protected function processGraphql(): void
    {
        foreach ($this->type->getFields() as $field) {
            $directives = $field->astNode->directives;
            $type = $field->getType();
            if (
                ($type instanceof ObjectType) ||
                ($type instanceof ListOfType) ||
                ($type instanceof UnionType) ||
                ($type instanceof NonNull && (
                    ($type->getWrappedType() instanceof ObjectType) ||
                    ($type->getWrappedType() instanceof ListOfType) ||
                    ($type->getWrappedType() instanceof UnionType)
                ))
            ) {
                // relationship
                $this->processRelationship($field, $directives);
            } else {
                list($type, $isRequired) = Parser::getUnwrappedType($field->getType());
                $typeName = $type->name;
                $this->processField($typeName, $field, $directives, $isRequired);
            }
        }

        /**
         * @var \GraphQL\Language\AST\NodeList<\GraphQL\Language\AST\DirectiveNode>|null
         */
        $directives = $this->type->astNode->directives;
        if ($directives) {
            $this->processTypeDirectives($directives, 'Model');
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
