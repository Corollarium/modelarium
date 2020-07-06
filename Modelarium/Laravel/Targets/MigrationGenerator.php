<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

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
        $lowerName = mb_strtolower($this->inflector->singularize($field->name));
        $lowerNamePlural = $this->inflector->pluralize($lowerName);

        if ($field->type instanceof NonNull) {
            $type = $field->type->getWrappedType();
        } else {
            $type = $field->type;
        }

        if ($field->type instanceof ListOfType) {
            $type = $field->type->getWrappedType();
        }

        $typeName = $type->name; /** @phpstan-ignore-line */

        $fieldName = $lowerName . '_id';

        $base = null;
        $extra = [];

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
                if (strcasecmp($type1, $type2) < 0) {
                    $item = $this->generateManyToManyTable($type1, $type2);
                    $this->collection->push($item);
                }
                break;
            case 'migrationForeign':
                $references = 'id';
                $on = $lowerNamePlural;
                $onDelete = null;
                $onUpdate = null;
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $value = $arg->value->value;

                    switch ($arg->name->value) {
                    case 'references':
                        $references = $value;
                    break;
                    case 'on':
                        $on = $value;
                    break;
                    case 'onDelete':
                        $onDelete = $value;
                    break;
                    case 'onUpdate':
                        $onUpdate = $value;
                    break;
                    }
                }
                $extra[] = '$table->foreign("' . $fieldName . '")' .
                    "->references(\"$references\")" .
                    "->on(\"$on\")" .
                    ($onDelete ? "->onDelete(\"$onDelete\")" : '') .
                    ($onUpdate ? "->onUpdate(\"$onUpdate\")" : '') .
                    ';';
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
        return $this->stubToString('migration', function ($stub) {
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

            assert($this->type->astNode !== null);
            /**
             * @var \GraphQL\Language\AST\NodeList|null
             */
            $directives = $this->type->astNode->directives;
            if ($directives) {
                $this->processDirectives($directives);
            }

            if ($this->mode === self::MODE_CREATE) {
                $stub = str_replace(
                    '// dummyCode',
                    join("\n            ", $this->createCode),
                    $stub
                );

                $stub = str_replace(
                    '// dummyPostCreateCode',
                    join("\n            ", $this->postCreateCode),
                    $stub
                );
            } else {
                $stub = str_replace(
                    '// dummyCode',
                    '// TODO: write the patch please',
                    $stub
                );

                $stub = str_replace(
                    '// dummyPostCreateCode',
                    '',
                    $stub
                );
            }

            $stub = str_replace(
                'dummytablename',
                $this->lowerNamePlural,
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                "# start graphql\n" .
                $this->currentModel .
                "\n# end graphql\n",
                $stub
            );
            return $stub;
        });
    }

    public function generateManyToManyTable(string $type1, string $type2): GeneratedItem
    {
        $contents = $this->stubToString('migration', function ($stub) use ($type1, $type2) {
            $code = <<<EOF

            \$table->increments("id");
            \$table->unsignedBigInteger("{$type1}_id");
            \$table->unsignedBigInteger("{$type2}_id");
EOF;

            $stub = str_replace(
                '// dummyCode',
                $code,
                $stub
            );

            $stub = str_replace(
                'dummytablename',
                "{$type1}_{$type2}",
                $stub
            );

            $stub = str_replace(
                'modelSchemaCode',
                '',
                $stub
            );
            return $stub;
        });

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

        return $item;
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
