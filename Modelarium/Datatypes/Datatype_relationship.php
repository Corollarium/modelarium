<?php declare(strict_types=1);

namespace Modelarium\Datatypes;

use Formularium\Datatype;
use Illuminate\Support\Str;

abstract class Datatype_relationship extends Datatype
{
    /**
     * One of RelationshipFactory con
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
    protected $sourceClass = '';

    /**
     * @var string
     */
    protected $target = '';

    /**
     * @var string
     */
    protected $targetClass= '';

    /**
     * If false, $source is "User" (class with HasOne/HasMany posts())
     * and $target is "Post" (class with belongsTo, users())
     *
     * If true, it's the inverse: $source is "Post" and $target is "User".
     *
     * @var bool
     */
    protected $isInverse;

    public function __construct(string $source, string $target, string $relationship, bool $isInverse)
    {
        $stringInverse = $isInverse ? 'inverse:' : '';
        $name = "relationship:{$stringInverse}{$relationship}:$source:$target";
        parent::__construct($name, 'relationship');
        $this->source = $source;
        $this->target = $target;
        $this->sourceClass = 'App\\Models\\' . Str::studly($this->source);
        $this->targetClass = 'App\\Models\\' . Str::studly($this->target);
        $this->relationship = $relationship;
        $this->isInverse = $isInverse;
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

    /**
     * @return  bool
     */
    public function getIsInverse()
    {
        return $this->isInverse;
    }

    /**
     * Get datatype name
     *
     * @return  string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get the value of target
     *
     * @return  string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Get the value of targetClass
     *
     * @return  string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }
}
