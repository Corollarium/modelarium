<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Formularium\CodeGenerator\LaravelEloquent\CodeGenerator as LaravelCodeGenerator;
use Formularium\Datatype;
use Formularium\Datatype\Datatype_enum;
use Formularium\Exception\ClassNotFoundException;
use Formularium\Factory\DatatypeFactory;
use Formularium\Field;
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
use function Safe\preg_match;

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

    /**
     * The last migration code
     *
     * @var string
     */
    protected $lastMigrationCode = null;

    /**
     * Time stamp
     *
     * @var string
     */
    protected $stamp = '';

    public function generate(): GeneratedCollection
    {
        $this->collection = new GeneratedCollection();
        $this->currentModel = \GraphQL\Language\Printer::doPrint($this->type->astNode);
        $this->stamp = date('Y_m_d_His');
        $filename = $this->generateFilename($this->lowerName);

        if ($this->mode !== self::MODE_NO_CHANGE) {
            $code = $this->generateString();
            if ($this->checkMigrationCodeChange($code)) {
                $item = new GeneratedItem(
                    GeneratedItem::TYPE_MIGRATION,
                    $code,
                    $filename
                );
                $this->collection->prepend($item);
            }
        }
        return $this->collection;
    }

    /**
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\NodeList<\GraphQL\Language\AST\DirectiveNode> $directives
     * @return void
     */
    protected function processBasetype(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $fieldName = $field->name;

        $required = false;
        if ($field->getType() instanceof NonNull) {
            $required = true;
            $type = $field->getType()->getWrappedType();
        } else {
            $type = $field->getType();
        }

        $codeFragment = new MigrationCodeFragment();
        $lcg = new LaravelCodeGenerator();
        $formulariumField = null;

        if ($type instanceof IDType) {
            $codeFragment->appendBase('$table->bigIncrements("id")');
        } elseif ($type instanceof StringType) {
            $formulariumField = new Field(
                $fieldName,
                'string',
                [],
                [ Datatype::REQUIRED => ['value' => $required ] ]
            );
        } elseif ($type instanceof IntType) {
            $formulariumField = new Field(
                $fieldName,
                'integer',
                [],
                [ Datatype::REQUIRED => ['value' => $required ] ]
            );
        } elseif ($type instanceof BooleanType) {
            $formulariumField = new Field(
                $fieldName,
                'boolean',
                [],
                [ Datatype::REQUIRED => ['value' => $required ] ]
            );
        } elseif ($type instanceof FloatType) {
            $formulariumField = new Field(
                $fieldName,
                'float',
                [],
                [ Datatype::REQUIRED => ['value' => $required ] ]
            );
        } elseif ($type instanceof EnumType) {
            $this->processEnum($field, $type, $codeFragment);
        } elseif ($type instanceof UnionType) {
            return;
        } elseif ($type instanceof CustomScalarType) {
            $ourType = $this->parser->getScalarType($type->name);
            if (!$ourType) {
                throw new Exception("Null scalar type: " . get_class($type));
            } elseif (!is_a($ourType, FormulariumScalarType::class) &&
                !is_a($ourType, \Modelarium\Types\ScalarType::class)
            ) {
                throw new Exception("Invalid extended scalar type: " . get_class($type));
            }
            /**
             * @var FormulariumScalarType $ourType
             */
            $formulariumField = new Field(
                $fieldName,
                $ourType->getDatatype(),
                [],
                [ Datatype::REQUIRED => ['value' => $required ] ]
            );
        } elseif ($type instanceof ListOfType) {
            throw new Exception("Invalid field type: " . get_class($type));
        } else {
            throw new Exception("Invalid field type: " . get_class($type));
        }

        if ($formulariumField) {
            $fieldList = $lcg->field($formulariumField);
            foreach (is_array($fieldList) ? $fieldList : [$fieldList] as $f) {
                $codeFragment->appendBase(
                    '$table->' . $f
                );
            }
        } elseif (!($field->getType() instanceof NonNull)) {
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
                'App\\Modelarium\\Datatype',
                'Tests\\Unit',
                function (ClassType $enumClass) use ($enumValues) {
                    $enumClass->addConstant('CHOICES', $enumValues);
                    $enumClass->getMethod('__construct')->addBody('$this->choices = self::CHOICES;');
                }
            );

            $path = base_path('app/Modelarium/Datatype');

            $retval = DatatypeFactory::generateFile(
                $code,
                $path,
                base_path('tests/Unit/')
            );

            $php = \Modelarium\Util::generateLighthouseTypeFile($type->name, 'App\\Modelarium\\Datatype\\Types');
            $filename = $path . "/Types/Datatype_{$type->name}.php";
            if (!is_dir($path . "/Types")) {
                \Safe\mkdir($path . "/Types", 0777, true);
            }
            \Safe\file_put_contents($filename, $php);

            // recreate scalars
            \Modelarium\Util::generateScalarFile('App\\Modelarium\\Datatype', base_path('graphql/types.graphql'));

            // load php files that were just created
            require_once($retval['filename']);
            require_once($filename);
            $this->parser->appendScalar($type->name, 'App\\Modelarium\\Datatype\\Types\\Datatype_' . $type->name);
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
            $this->warn('Enum had its possible values changed. Please review the datatype class.');
        }

        $lcg = new LaravelCodeGenerator();
        $fieldList = $lcg->field(
            new Field($fieldName, $ourType->getDatatype())
        );
        foreach (is_array($fieldList) ? $fieldList : [$fieldList] as $f) {
            $codeFragment->appendBase(
                '$table->' . $f
            );
        }
    }

    /**
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\NodeList<\GraphQL\Language\AST\DirectiveNode> $directives
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
            return;
        }

        $codeFragment = new MigrationCodeFragment();

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            if ($name === 'migrationSkip') {
                return;
            }

            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::processMigrationRelationshipDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $field,
                    $directive,
                    $codeFragment
                );
            }
        }

        if ($codeFragment->base) {
            if (!($field->getType() instanceof NonNull)) {
                $codeFragment->appendBase('->nullable()');
            }
            $this->createCode[] = '$table' . $codeFragment->base . ';';
        }

        $this->createCode = array_merge($this->createCode, $codeFragment->extraLines);
    }

    public function generateString(): string
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
                $this->processBasetype($field, $directives);
            }
        }

        assert($this->type->astNode !== null);
        /**
         * @var \GraphQL\Language\AST\NodeList<\GraphQL\Language\AST\DirectiveNode>|null
         */
        $directives = $this->type->astNode->directives;
        if ($directives) {
            $this->processTypeDirectives($directives, 'Migration');
        }

        $context = [
            'dummytablename' => $this->tableName,
            'modelSchemaCode' => "# start graphql\n" .
                $this->currentModel .
                "\n# end graphql",
        ];

        if ($this->mode === self::MODE_CREATE) {
            if ($this->lowerName == 'user') {
                $context['className'] = 'CreateUsers';
            } else {
                $context['className'] = 'Create' . $this->studlyName . str_replace('_', '', $this->stamp);
            }
            $context['upOperation'] = 'create';
            $context['downOperation'] = 'dropIfExists';
            $context['dummyCode'] = join("\n            ", $this->createCode);
            $context['dummyInverseCode'] = null;
            $context['dummyPostCreateCode'] = join("\n            ", $this->postCreateCode);
        } else {
            $context['className'] = 'Patch' . $this->studlyName . str_replace('_', '', $this->stamp);
            $context['upOperation'] = 'table';
            $context['downOperation'] = 'table';
            $context['dummyCode'] = '// TODO: write the patch please';
            $context['dummyInverseCode'] = '// TODO: write the inverse patch please';
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
    public function generateManyToManyMorphTable(string $name, string $relation): string
    {
        $dummyCode = <<<EOF

            \$table->unsignedBigInteger("{$name}_id");
            \$table->unsignedBigInteger("{$relation}_id");
            \$table->string("{$relation}_type");
EOF;
        $context = [
            'dummyCode' => $dummyCode,
            'upOperation' => 'create',
            'downOperation' => 'dropIfExists',
            'dummytablename' => $this->getInflector()->pluralize($relation), // TODO: check, toTableName()?
            'modelSchemaCode' => ''
        ];
        $contents = $this->templateStub('migration', $context);

        $item = new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $contents,
            $this->getBasePath(
                'database/migrations/' .
                $this->stamp .
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
    public function generateManyToManyTable(string $type1, string $type2): string
    {
        $dummyCode = <<<EOF

            \$table->increments("id");
            \$table->unsignedBigInteger("{$type1}_id")->references('id')->on('{$type1}');
            \$table->unsignedBigInteger("{$type2}_id")->references('id')->on('{$type2}');
EOF;
        $context = [
            'dummyCode' => $dummyCode,
            'upOperation' => 'create',
            'downOperation' => 'dropIfExists',
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
                $this->stamp .
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
        $match = '/(patch|create)_' . preg_quote($basename) . '_(table|[0-9])/';

        $basepath = $this->getBasePath('database/migrations/');
        if (is_dir($basepath)) {
            $migrationFiles = \Safe\scandir($basepath);
            rsort($migrationFiles);
            foreach ($migrationFiles as $m) {
                if (!preg_match($match, $m)) {
                    continue;
                }

                // get source
                $this->lastMigrationCode = \Safe\file_get_contents($basepath . '/' . $m);

                // compare with this source
                $model = trim(getStringBetween($this->lastMigrationCode, '# start graphql', '# end graphql'));

                // if equal ignore and don't output file
                if ($model === trim($this->currentModel)) {
                    $this->mode = self::MODE_NO_CHANGE;
                } else {
                    // else we'll generate a diff and patch
                    $this->mode = self::MODE_PATCH;
                }
                break;
            }
        }

        if ($this->mode === self::MODE_CREATE && $this->lowerName === 'user') {
            return $this->getBasePath(
                'database/migrations/2014_10_12_000000_create_users_table.php'
            );
        }

        return $this->getBasePath(
            'database/migrations/' .
            $this->stamp .
            str_pad((string)(static::$counter++), 3, "0", STR_PAD_LEFT) . // so we keep the same order of types in schema
            '_' . $this->mode . '_' .
            $basename . '_' .
            str_replace('_', '', $this->stamp) . '_' .
            'table' .
            '.php'
        );
    }

    /**
     * Compares with the latest migration
     *
     * @param string $newcode
     * @return boolean
     */
    protected function checkMigrationCodeChange(string $newcode): bool
    {
        if (!$this->lastMigrationCode) {
            return true;
        }
        $tokens = token_get_all($this->lastMigrationCode);
        for ($i=0, $z=count($tokens); $i < $z; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION
                && is_array($tokens[$i+1]) && $tokens[$i+1][0] == T_WHITESPACE
                && is_array($tokens[$i+2]) && $tokens[$i+2][1] == 'up'
            ) {
                $accumulator = [];
                $braceDepth = 0;
                // collect tokens from function head through opening brace
                while ($tokens[$i] != '{' && ($i < $z)) {
                    $accumulator[] = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $i++;
                }
                if ($i == $z) {
                    // handle error
                } else {
                    // note, accumulate, and position index past brace
                    $braceDepth = 1;
                    $accumulator[] = '{';
                    $i++;
                }
                while ($braceDepth > 0 && ($i < $z)) {
                    if (is_array($tokens[$i])) {
                        $accumulator[] = $tokens[$i][1];
                    } else {
                        $accumulator[] = $tokens[$i];
                        if ($tokens[$i] == '{') {
                            $braceDepth++;
                        } elseif ($tokens[$i] == '}') {
                            $braceDepth--;
                        }
                    }
                    $i++;
                }
                $functionSrc = implode("", $accumulator);
                if ($functionSrc == $newcode) {
                    return false;
                }
            }
        }

        return true;
    }
}
