<?php declare(strict_types=1);

namespace Modelarium\Datatypes;

use Formularium\Datatype;
use Formularium\Exception\ClassNotFoundException;

use function Safe\class_alias;
use function Safe\preg_match;

/**
 * Factory to create relationship datatypes.
 */
abstract class RelationshipFactory
{
    public const RELATIONSHIP = "RELATIONSHIP";
    public const RELATIONSHIP_ONE_TO_ONE = "RELATIONSHIP_ONE_TO_ONE";
    public const RELATIONSHIP_ONE_TO_MANY = "RELATIONSHIP_ONE_TO_MANY";
    public const RELATIONSHIP_MANY_TO_MANY  = "RELATIONSHIP_MANY_TO_MANY";
    public const MORPH_ONE_TO_ONE = "RELATIONSHIP_ONE_TO_ONE";
    public const MORPH_ONE_TO_MANY = "RELATIONSHIP_ONE_TO_MANY";
    public const MORPH_MANY_TO_MANY  = "RELATIONSHIP_MANY_TO_MANY";
    private function __construct()
    {
        // nothing
    }

    /**
     * Returns a Datatype given the relationship name
     *
     * @param string $name relationship[:inverse?]:[mode]:[source]:[target]
     * @return Datatype
     */
    public static function factoryName(string $name): Datatype
    {
        $matches = [];
        if (preg_match(
            '/^relationship(?P<inverse>inverse)?:(?P<mode>11|1N|N1|NN):(?P<source>[a-zA-Z0-9]+):(?P<target>[a-zA-Z0-9]+)$/',
            $name,
            $matches
        )) {
            $mode = null;
            switch ($matches['mode']) {
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
            return static::factory($matches['source'], $matches['target'], $mode, (bool)$matches['inverse']);
        }
        throw new ClassNotFoundException('Invalid relationship');
    }

    /**
     * Factory
     *
     * @param string $source
     * @param string $target
     * @param string $relationship
     * @param boolean $isInverse
     * @return Datatype
     */
    public static function factory(string $source, string $target, string $relationship, bool $isInverse): Datatype
    {
        $namespace = static::getNamespace();
        $inverse = $isInverse ? '_inverse' : '';
        $className = "Datatype_{$relationship}{$inverse}_{$source}_{$target}";
        $fqn = "$namespace\\$className";
        if (!class_exists($fqn)) {
            class_alias(static::class, $fqn, true);
        }
        return new $fqn($source, $target, $relationship, $isInverse);
    }

    /**
     * We need the proper namespace, so this will be implemented in the actual namespace directory.
     *
     * @return string
     */
    abstract public static function getNamespace(): string;
}
