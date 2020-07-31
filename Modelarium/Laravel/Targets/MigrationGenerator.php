<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

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
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;

use function Safe\rsort;

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
    protected $createCode = [];

    /**
     * Code used post the create() call
     *
     * @var string[]
     */
    protected $postCreateCode = [];

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
        $extra = [];

        // TODO: scalars

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        $base = '';
        if ($type instanceof IDType) {
            $base = '$table->bigIncrements("id")';
        } elseif ($type instanceof StringType) {
            $base = '$table->string("' . $fieldName . '")';
        } elseif ($type instanceof IntType) {
            $base = '$table->integer("' . $fieldName . '")';
        } elseif ($type instanceof BooleanType) {
            $base = '$table->bool("' . $fieldName . '")';
        } elseif ($type instanceof FloatType) {
            $base = '$table->float("' . $fieldName . '")';
        } elseif ($type instanceof EnumType) {
            throw new Exception("Enum is not supported here as a type field");
        } elseif ($type instanceof UnionType) {
            return;
        } elseif ($type instanceof CustomScalarType) {
            $ourType = $this->parser->getScalarType($type->name);
            if (!$ourType) {
                throw new Exception("Invalid extended scalar type: " . get_class($type));
            }
            $options = []; // TODO: from directives
            $base = '$table->' . $ourType->getLaravelSQLType($fieldName, $options);
        } elseif ($type instanceof ListOfType) {
            throw new Exception("Invalid field type: " . get_class($type));
        } else {
            throw new Exception("Invalid field type: " . get_class($type));
        }

        if (!($field->type instanceof NonNull)) {
            $base .= '->nullable()';
        }

        foreach ($directives as $directive) {
            /**
             * @var DirectiveNode $directive
             */
            $name = $directive->name->value;
            switch ($name) {
            case 'migrationUniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'migrationIndex':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'migrationDefaultValue':
                $x = ''; // TODO
                Parser::getDirectiveArgumentByName($directive, 'value');
                $base .= '->default(' . $x . ')';
                throw new Exception('Default value not implemented yet');
                // break;
            }
        }
        $base .= ';';

        $this->createCode[] = $base;
        $this->createCode = array_merge($this->createCode, $extra);
    }

    protected function processRelationship(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $lowerName = mb_strtolower($this->getInflector()->singularize($field->name));
        $lowerNamePlural = $this->getInflector()->pluralize($lowerName);
        $fieldName = $lowerName . '_id';

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name; /** @phpstan-ignore-line */

        $base = null;
        $extra = [];

        // special types that should be skipped.
        if ($typeName === 'Can') {
            return;
        }

        $isManytoMany = false;
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'migrationSkip':
                return;
            case 'migrationUniqueIndex':
                $extra[] = '$table->unique("' . $fieldName . '");';
                break;
            case 'migrationIndex':
                $extra[] = '$table->index("' . $fieldName . '");';
                break;
            case 'belongsTo':
                $targetType = $this->parser->getType($typeName);
                if (!$targetType) {
                    throw new Exception("Cannot get type {$typeName} as a relationship to {$this->name}");
                } elseif (!($targetType instanceof ObjectType)) {
                    throw new Exception("{$typeName} is not a type for a relationship to {$this->name}");
                }
                try {
                    $targetField = $targetType->getField($this->lowerName); // TODO: might have another name than lowerName
                } catch (\GraphQL\Error\InvariantViolation $e) {
                    $targetField = $targetType->getField($this->lowerNamePlural);
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
                $isManytoMany = true;
                if (strcasecmp($type1, $type2) < 0) {
                    $this->generateManyToManyTable($type1, $type2);
                }
                break;

            case 'morphTo':
                $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
                $base = '$table->unsignedBigInteger("' . $relation . '_id")';
                $extra[] = '$table->string("' . $relation . '_type")';
                break;

            case 'morphedByMany':
                $isManytoMany = true;
                $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
                $this->generateManyToManyMorphTable($this->lowerName, $relation);
                break;
            }
        }

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'migrationForeign':
                
                if (!$isManytoMany) {
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
                        ($arguments['onDelete'] ? "->onDelete(\"{$arguments['onDelete']}\")" : '') .
                        ($arguments['onUpdate'] ? "->onUpdate(\"{$arguments['onUpdate']}\")" : '') .
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
            switch ($name) {
            case 'migrationSoftDeletes':
                $this->createCode[] ='$table->softDeletes();';
                break;
            case 'migrationPrimaryIndex':
                // TODO
                throw new Exception("Primary index is not implemented yet");
            case 'migrationIndex':
                $values = $directive->arguments[0]->value->values;

                $indexFields = [];
                foreach ($values as $value) {
                    $indexFields[] = $value->value;
                }
                if (!count($indexFields)) {
                    throw new Exception("You must provide at least one field to an index");
                }
                $this->createCode[] ='$table->index("' . implode('", "', $indexFields) .'");';
                break;
            case 'migrationSpatialIndex':
                $this->createCode[] ='$table->spatialIndex("' . $directive->arguments[0]->value->value .'");';
                break;

            case 'migrationFulltextIndex':
                $values = $directive->arguments[0]->value->values;

                $indexFields = [];
                foreach ($values as $value) {
                    $indexFields[] = $value->value;
                }

                if (!count($indexFields)) {
                    throw new Exception("You must provide at least one field to a full text index");
                }
                $this->postCreateCode[] = "DB::statement('ALTER TABLE " .
                    $this->lowerNamePlural .
                    " ADD FULLTEXT fulltext_index (\"" .
                    implode('", "', $indexFields) .
                    "\")');";
                break;
            case 'migrationRememberToken':
                $this->createCode[] ='$table->rememberToken();';
                break;
            case 'migrationTimestamps':
                $this->createCode[] ='$table->timestamps();';
                break;
            default:
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
            'dummytablename' => $this->lowerNamePlural,
            'modelSchemaCode' => "# start graphql\n" .
                $this->currentModel .
                "\n# end graphql",
        ];

        if ($this->mode === self::MODE_CREATE) {
            $context['dummyCode'] = join("\n            ", $this->createCode);
            $context['dummyPostCreateCode'] = join("\n            ", $this->postCreateCode);
        } else {
            $context['dummyCode'] = '// TODO: write the patch please';
            $context['dummyPostCreateCode'] = '';
        }

        return $this->templateStub('migration', $context);
    }

    /**
     * creates a many-to-many morph relationship table
     *
     * @param string $type1
     * @param string $type2
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
            'dummytablename' => $this->getInflector()->pluralize($relation),
            'modelSchemaCode' => ''
        ];
        $contents = $this->templateStub('migration', $context);

        $item = new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $contents,
            $this->getBasePath(
                'database/migrations/' .
                date('Y_m_d_His') .
                static::$counter++ . // so we keep the same order of types in schema
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
            'modelSchemaCode' => ''
        ];
        $contents = $this->templateStub('migration', $context);

        $item = new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $contents,
            $this->getBasePath(
                'database/migrations/' .
                date('Y_m_d_His') .
                static::$counter++ . // so we keep the same order of types in schema
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
            static::$counter++ . // so we keep the same order of types in schema
            '_' . $this->mode . '_' .
            $basename . '_table.php'
        );
    }
}
