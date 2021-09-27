<?php declare(strict_types=1);

namespace Modelarium\Datatype;

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
    public const RELATIONSHIP_ONE_TO_ONE = "11";
    public const RELATIONSHIP_ONE_TO_MANY = "1N";
    public const RELATIONSHIP_MANY_TO_MANY  = "NN";
    public const MORPH_ONE_TO_ONE = "M11";
    public const MORPH_ONE_TO_MANY = "M1N";
    public const MORPH_MANY_TO_MANY  = "MNN";

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
            '/^relationship(?P<inverse>:inverse)?:(?P<mode>M?(11|1N|N1|NN)):(?P<source>[a-zA-Z0-9]+):(?P<target>[a-zA-Z0-9]+)$/',
            $name,
            $matches
        )) {
            $relationships = [
                self::RELATIONSHIP_ONE_TO_ONE,
                self::RELATIONSHIP_ONE_TO_MANY,
                self::RELATIONSHIP_MANY_TO_MANY,
                self::MORPH_ONE_TO_ONE,
                self::MORPH_ONE_TO_MANY,
                self::MORPH_MANY_TO_MANY,
            ];
            if (!in_array($matches['mode'], $relationships)) {
                throw new ClassNotFoundException('Invalid relationship');
            }
            return static::factory($matches['source'], $matches['target'], $matches['mode'], (bool)$matches['inverse']);
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
            class_alias($namespace . "\\Datatype_relationship", $fqn, true);
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
