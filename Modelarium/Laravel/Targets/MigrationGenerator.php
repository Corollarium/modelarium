<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\Datatype\Datatype_enum;
use Formularium\Exception\ClassNotFoundException;
use Formularium\Factory\DatatypeFactory;
use Illuminate\Support\Str;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\UnionType;
use Modelarium\BaseGenerator;
use Modelarium\Exception\Exception;
use Modelarium\Exception\ScalarNotFoundException;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;
use Modelarium\Types\FormulariumScalarType;
use Nette\PhpGenerator\ClassType;

use function Safe\array_combine;
use function Safe\rsort;
use function Safe\date;

function getStringBetween(string $string, string $start, string $end): string
{
    $ini = mb_strpos($string, $start);
    if ($ini === false) {
        return '';
    }
    $ini += mb_strlen($start);
    $len = mb_strpos($string, $end, $ini) - $ini;
    return mb_substr($string, $ini, $len);
}

function endsWith(string $haystack, string $needle): bool
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}
class MigrationGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

    protected const MODE_CREATE = 'create';
    protected const MODE_PATCH = 'patch';
    protected const MODE_NO_CHANGE = 'nochange';

    /**
     * Unique counter
     *
     * @var integer
     */
    public static $counter = 0;

    /**
     * @var ObjectType
     */
    protected $type = null;

    /**
     * @var GeneratedCollection
     */
    protected $collection = null;

    /**
     * Code used in the create() call
     *
     * @var string[]
     */
    public $createCode = [];

    /**
     * Code used post the create() call
     *
     * @var string[]
     */
    public $postCreateCode = [];

    /**
     * 'create' or 'patch'
     *
     * @var string
     */
    protected $mode = self::MODE_CREATE;

    /**
     * Code used in the create() call
     *
     * @var string
     */
    protected $currentModel = '';

    public function generate(): GeneratedCollection
    {
        $this->collection = new GeneratedCollection();
        $this->currentModel = \GraphQL\Language\Printer::doPrint($this->type->astNode);
        $filename = $this->generateFilename($this->lowerName);

        if ($this->mode !== self::MODE_NO_CHANGE) {
            $item = new GeneratedItem(
                GeneratedItem::TYPE_MIGRATION,
                $this->generateString(),
                $filename
            );
            $this->collection->prepend($item);
        }
        return $this->collection;
    }

    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        $codeFragment = new MigrationCodeFragment();

        if ($type instanceof IDType) {
            $codeFragment->appendBase('$table->bigIncrements("id")');
        } elseif ($type instanceof StringType) {
            $codeFragment->appendBase('$table->string("' . $fieldName . '")');
        } elseif ($type instanceof IntType) {
            $codeFragment->appendBase('$table->integer("' . $fieldName . '")');
        } elseif ($type instanceof BooleanType) {
            $codeFragment->appendBase('$table->boolean("' . $fieldName . '")');
        } elseif ($type instanceof FloatType) {
            $codeFragment->appendBase('$table->float("' . $fieldName . '")');
        } elseif ($type instanceof EnumType) {
            $this->processEnum($field, $type, $codeFragment);
        } elseif ($type instanceof UnionType) {
            return;
        } elseif ($type instanceof CustomScalarType) {
            $ourType = $this->parser->getScalarType($type->name);
            if (!$ourType) {
                throw new Exception("Invalid extended scalar type: " . get_class($type));
            }
            $options = []; // TODO: from directives
            $codeFragment->appendBase('$table->' . $ourType->getLaravelSQLType($fieldName, $options));
        } elseif ($type instanceof ListOfType) {
            throw new Exception("Invalid field type: " . get_class($type));
        } else {
            throw new Exception("Invalid field type: " . get_class($type));
        }

        if (!($field->type instanceof NonNull)) {
            $codeFragment->appendBase('->nullable()');
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            if ($name === 'migrationSkip') { // special case
                return;
            }

            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processMigrationFieldDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $field,
                    $directive,
                    $codeFragment
                );
            }
        }

        $this->createCode[] = $codeFragment->base . ';';
        $this->createCode = array_merge($this->createCode, $codeFragment->extraLines);
    }

    protected function processEnum(
        \GraphQL\Type\Definition\FieldDefinition $field,
        EnumType $type,
        MigrationCodeFragment $codeFragment
    ): void {
        $fieldName = $field->name;
        $ourType = $this->parser->getScalarType($type->name);
        $parsedValues = $type->config['values'];

        if (!$ourType) {
            $parsedKeys = array_keys($parsedValues);
            $enumValues = array_combine($parsedKeys, $parsedKeys);

            // let's create this for the user
            $code = DatatypeFactory::generate(
                $type->name,
                'enum',
                'App\\Datatypes',
                'Tests\\Unit',
                function (ClassType $enumClass) use ($enumValues) {
                    $enumClass->addConstant('CHOICES', $enumValues);
                    $enumClass->getMethod('__construct')->addBody('$this->choices = self::CHOICES;');
                }
            );
    
            $path = base_path('app/Datatypes');
            $lowerTypeName = mb_strtolower($type->name);

            $retval = DatatypeFactory::generateFile(
                $code,
                $path,
                base_path('tests/Unit/')
            );

            $php = \Modelarium\Util::generateLighthouseTypeFile($lowerTypeName, 'App\\Datatypes\\Types');
            $filename = $path . "/Types/Datatype_{$lowerTypeName}.php";
            if (!is_dir($path . "/Types")) {
                \Safe\mkdir($path . "/Types", 0777, true);
            }
            \Safe\file_put_contents($filename, $php);
    
            // recreate scalars
            \Modelarium\Util::generateScalarFile('App\\Datatypes', base_path('graphql/types.graphql'));

            // load php files that were just created
            require_once($retval['filename']);
            require_once($filename);
            $this->parser->appendScalar($type->name, 'App\\Datatypes\\Types\\Datatype_' . $lowerTypeName);
            $ourType = $this->parser->getScalarType($type->name);
        }
        if (!($ourType instanceof FormulariumScalarType)) {
            throw new Exception("Enum {$type->name} {$fieldName} is not a FormulariumScalarType");
        }

        /**
         * @var FormulariumScalarType $ourType
         */
        /**
         * @var Datatype_enum $ourDatatype
         */
        $ourDatatype = $ourType->getDatatype();
        $currentChoices = $ourDatatype->getChoices();
        if (array_diff_key($currentChoices, $parsedValues) || array_diff_key($parsedValues, $currentChoices)) {
            // TODO???
        }

        $options = []; // TODO: from directives
        $codeFragment->appendBase('$table->'  . $ourType->getLaravelSQLType($fieldName, $options));
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $lowerName = mb_strtolower($this->getInflector()->singularize($field->name));
        $lowerNamePlural = $this->getInflector()->pluralize($lowerName);
        $fieldName = $lowerName . '_id';

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name;
        $tableName = self::toTableName($typeName);

        $base = null;
        $extra = [];

        // special types that should be skipped.
        if ($typeName === 'Can') {
            return;
        }

        $isManyToMany = false;
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            if ($name === 'migrationSkip') {
                return;
            }

            // TODO: separate classes
            // $className = $this->getDirectiveClass($name);
            // if ($className) {
            //     $methodName = "$className::processMigrationRelationshipDirective";
            //     /** @phpstan-igno re-next-line */
            //     $methodName(
            //         $this,
            //         $field,
            //         $directive
            //     );
            // }

            switch ($name) {
            case 'migrationUniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'migrationIndex':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'belongsTo':
                $targetType = $this->parser->getType($typeName);
                if (!$targetType) {
                    throw new Exception("Cannot get type {$typeName} as a relationship to {$this->baseName}");
                } elseif (!($targetType instanceof ObjectType)) {
                    throw new Exception("{$typeName} is not a type for a relationship to {$this->baseName}");
                }
                // we don't know what is the reverse relationship name at this point. so let's guess all possibilities
                try {
                    $targetField = $targetType->getField($tableName);
                } catch (\GraphQL\Error\InvariantViolation $e) {
                    try {
                        $targetField = $targetType->getField($this->tableName);
                    } catch (\GraphQL\Error\InvariantViolation $e) {
                        // one to one
                        $targetField = $targetType->getField($this->lowerName);
                    }
                }

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

            case 'belongsToMany':
                $type1 = $this->lowerName;
                $type2 = $lowerName;

                // we only generate once, so use a comparison for that
                $isManyToMany = true;
                if (strcasecmp($type1, $type2) < 0) {
                    $this->generateManyToManyTable($type1, $type2);
                }
                break;

            case 'morphTo':
                $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
                $base = '$table->unsignedBigInteger("' . $relation . '_id")';
                $extra[] = '$table->string("' . $relation . '_type")' .
                    ($isRequired ? '' : '->nullable()') . ';';
                break;

            case 'morphedByMany':
                $isManyToMany = true;
                $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
                $this->generateManyToManyMorphTable($this->lowerName, $relation);
                break;
            }
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'migrationForeign':
                
                if (!$isManyToMany) {
                    $arguments = array_merge(
                        [
                            'references' => 'id',
                            'on' => $lowerNamePlural
                        ],
                        Parser::getDirectiveArguments($directive)
                    );
    
                    $extra[] = '$table->foreign("' . $fieldName . '")' .
                        "->references(\"{$arguments['references']}\")" .
                        "->on(\"{$arguments['on']}\")" .
                        (($arguments['onDelete'] ?? '') ? "->onDelete(\"{$arguments['onDelete']}\")" : '') .
                        (($arguments['onUpdate'] ?? '') ? "->onUpdate(\"{$arguments['onUpdate']}\")" : '') .
                        ';';
                }
                break;
            }
        }

        if ($base) {
            if (!($field->type instanceof NonNull)) {
                $base .= '->nullable()';
            }
            $base .= ';';
            $this->createCode[] = $base;
        }
        $this->createCode = array_merge($this->createCode, $extra);
    }

    protected function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processMigrationTypeDirective";
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

        assert($this->type->astNode !== null);
        /**
         * @var \GraphQL\Language\AST\NodeList|null
         */
        $directives = $this->type->astNode->directives;
        if ($directives) {
            $this->processDirectives($directives);
        }

        $context = [
            'dummytablename' => $this->tableName,
            'modelSchemaCode' => "# start graphql\n" .
                $this->currentModel .
                "\n# end graphql",
        ];

        if ($this->mode === self::MODE_CREATE) {
            $context['className'] = 'Create' . $this->studlyName;
            $context['dummyCode'] = join("\n            ", $this->createCode);
            $context['dummyPostCreateCode'] = join("\n            ", $this->postCreateCode);
        } else {
            $context['className'] = 'Patch' . $this->studlyName . date('YmdHis');
            $context['dummyCode'] = '// TODO: write the patch please';
            $context['dummyPostCreateCode'] = '';
        }

        return $this->templateStub('migration', $context);
    }

    /**
     * creates a many-to-many morph relationship table
     *
     * @param string $name
     * @param string $relation
     * @return string The table name.
     */
    protected function generateManyToManyMorphTable(string $name, string $relation): string
    {
        $dummyCode = <<<EOF

            \$table->unsignedBigInteger("{$name}_id");
            \$table->unsignedBigInteger("{$relation}_id");
            \$table->string("{$relation}_type");
EOF;
        $context = [
            'dummyCode' => $dummyCode,
            'dummytablename' => $this->getInflector()->pluralize($relation), // TODO: check, toTableName()?
            'modelSchemaCode' => ''
        ];
        $contents = $this->templateStub('migration', $context);

        $item = new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $contents,
            $this->getBasePath(
                'database/migrations/' .
                date('Y_m_d_His') .
                str_pad((string)(static::$counter++), 3, "0", STR_PAD_LEFT) . // so we keep the same order of types in schema
                '_' . $this->mode . '_' .
                $relation .
                '_table.php'
            )
        );
        $this->collection->push($item);

        return $context['dummytablename'];
    }

    /**
     * creates a many-to-many relationship table
     *
     * @param string $type1
     * @param string $type2
     * @return string The table name.
     */
    protected function generateManyToManyTable(string $type1, string $type2): string
    {
        $dummyCode = <<<EOF

            \$table->increments("id");
            \$table->unsignedBigInteger("{$type1}_id")->references('id')->on('{$type1}');
            \$table->unsignedBigInteger("{$type2}_id")->references('id')->on('{$type2}');
EOF;
        $context = [
            'dummyCode' => $dummyCode,
            'dummytablename' => "{$type1}_{$type2}",
            'className' => Str::studly($this->mode) . Str::studly($type1) . Str::studly($type2),
            'modelSchemaCode' => ''
        ];
        $contents = $this->templateStub('migration', $context);

        $item = new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $contents,
            $this->getBasePath(
                'database/migrations/' .
                date('Y_m_d_His') .
                str_pad((string)(static::$counter++), 3, "0", STR_PAD_LEFT) . // so we keep the same order of types in schema
                '_' . $this->mode . '_' .
                $type1 . '_' . $type2 .
                '_table.php'
            )
        );
        $this->collection->push($item);

        return $context['dummytablename'];
    }

    protected function generateFilename(string $basename): string
    {
        $this->mode = self::MODE_CREATE;
        $match = '_' . $basename . '_table.php';

        $basepath = $this->getBasePath('database/migrations/');
        if (is_dir($basepath)) {
            $migrationFiles = \Safe\scandir($basepath);
            rsort($migrationFiles);
            foreach ($migrationFiles as $m) {
                if (!endsWith($m, $match)) {
                    continue;
                }

                // get source
                $data = \Safe\file_get_contents($basepath . '/' . $m);

                // compare with this source
                $model = trim(getStringBetween($data, '# start graphql', '# end graphql'));

                // if equal ignore and don't output file
                if ($model === $this->currentModel) {
                    $this->mode = self::MODE_NO_CHANGE;
                } else {
                    // else we'll generate a diff and patch
                    $this->mode = self::MODE_PATCH;
                }
                break;
            }
        }

        return $this->getBasePath(
            'database/migrations/' .
            date('Y_m_d_His') .
            str_pad((string)(static::$counter++), 3, "0", STR_PAD_LEFT) . // so we keep the same order of types in schema
            '_' . $this->mode . '_' .
            $basename . '_table.php'
        );
    }
}
