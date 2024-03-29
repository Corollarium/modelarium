<?php declare(strict_types=1);

namespace Modelarium\Datatype;

use Formularium\Datatype;
use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Str;
use Modelarium\BaseGenerator;

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
        $this->sourceClass = 'App\\Models\\' . $this->source;
        $this->targetClass = 'App\\Models\\' . $this->target;
        $this->relationship = $relationship;
        $this->isInverse = $isInverse;
    }

    public function getDefault()
    {
        return 0;
    }

    /**
     * @return  bool
     */
    public function getIsInverse(): bool
    {
        return $this->isInverse;
    }

    /**
     * Get datatype name
     *
     * @return  string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the value of target
     *
     * @return  string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get the value of targetClass
     *
     * @return  string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    public function getTargetPlural(): string
    {
        $inflector = InflectorFactory::create()->build();
        return $inflector->pluralize($this->getTarget());
    }

    public function getTargetTable(): string
    {
        return BaseGenerator::toTableName($this->target);
    }

    /**
     * Get one of RelationshipFactory con
     *
     * @return  string
     */
    public function getRelationship(): string
    {
        return $this->relationship;
    }

    public function isMorph(): bool
    {
        return
            $this->relationship === RelationshipFactory::MORPH_ONE_TO_MANY ||
            $this->relationship === RelationshipFactory::MORPH_ONE_TO_ONE ||
            $this->relationship === RelationshipFactory::MORPH_MANY_TO_MANY;
    }
}
