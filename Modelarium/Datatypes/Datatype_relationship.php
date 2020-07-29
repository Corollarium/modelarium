<?php declare(strict_types=1);

namespace Modelarium\Datatypes;

use Formularium\Datatype;
use Illuminate\Support\Str;
use Formularium\Exception\ClassNotFoundException;

use function Safe\class_alias;
use function Safe\preg_match;

abstract class Datatype_relationship extends Datatype
{
    public const RELATIONSHIP = "RELATIONSHIP";
    public const RELATIONSHIP_ONE_TO_ONE = "RELATIONSHIP_ONE_TO_ONE";
    public const RELATIONSHIP_ONE_TO_MANY = "RELATIONSHIP_ONE_TO_MANY";
    public const RELATIONSHIP_MANY_TO_MANY  = "RELATIONSHIP_MANY_TO_MANY";
    public const MORPH_ONE_TO_ONE = "RELATIONSHIP_ONE_TO_ONE";
    public const MORPH_ONE_TO_MANY = "RELATIONSHIP_ONE_TO_MANY";
    public const MORPH_MANY_TO_MANY  = "RELATIONSHIP_MANY_TO_MANY";

    /**
     * @param string $name relationship:[mode]:[source]:[target]
     * @return Datatype
     */
    public static function factoryName(string $name): Datatype
    {
        $matches = [];
        if (preg_match('/^relationship:(11|1N|N1|NN):([a-zA-Z0-9]+):([a-zA-Z0-9]+)$/', $name, $matches)) {
            $mode = null;
            switch ($matches[1]) {
                case '11':
                    $mode = self::RELATIONSHIP_ONE_TO_ONE;
                    break;
                case '1N':
                    $mode = self::RELATIONSHIP_ONE_TO_MANY;
                    break;
                case 'NN':
                case 'N1': // TODO
                        $mode = self::RELATIONSHIP_MANY_TO_MANY;
                    break;
                // TODO: morph
                default:
                    throw new ClassNotFoundException('Invalid relationship');
            }
            return static::factory($matches[2], $matches[3], $matches[1]); // $mode);
        }
        throw new ClassNotFoundException('Invalid relationship');
    }

    abstract public static function getNamespace(): string;

    public static function factory(string $from, string $to, string $relationship): Datatype
    {
        $namespace = static::getNamespace();
        $className = "Datatype_{$relationship}_{$from}_{$to}";
        $fqn = "$namespace\\$className";
        if (!class_exists($fqn)) {
            class_alias(static::class, $fqn, true);
        }
        return new $fqn($from, $to, $relationship);
    }

    /**
     * @var string
     */
    protected $relationship = '';

    /**
     * @var string
     */
    protected $source = '';

    /**
     * @var string
     */
    protected $targetClass= '';

    /**
     * @var string
     */
    protected $target = '';

    public function __construct(string $source, string $target, string $relationship)
    {
        $name = "relationship:$relationship:$source:$target";
        parent::__construct($name, 'relationship');
        $this->source = $source;
        $this->target = $target;
        $this->targetClass = 'App\\Models\\' . Str::studly($this->target);
        $this->relationship = $relationship;
    }

    public function getDefault()
    {
        return 0;
    }

    public function getSQLType(string $database = '', array $options = []): string
    {
        return 'BIGINT';
    }

    public function getLaravelSQLType(string $name, array $options = []): string
    {
        return "unsignedBigInteger(\"{$name}_id\")";
    }
}
