<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Datatype;
use Formularium\Exception\Exception;
use Formularium\Field;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\CustomScalarType as GraphQLScalarType;

abstract class ScalarType extends GraphQLScalarType
{
    /**
     * @var Datatype
     */
    protected $datatype = null;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        try {
            $this->datatype = Datatype::factory($this->name);
        } catch (\Formularium\Exception\ClassNotFoundException $e) {
        }
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @param string $value
     * @return string
     */
    public function serialize($value)
    {
        return $this->datatype->format($value, $this->validators);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        return $this->datatype->validate($value, $this->validators);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * E.g.
     * {
     *   user(email: "user@example.com")
     * }
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     * @param array|null $variables
     * @return string
     * @throws Error
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        return $this->parseValue($valueNode->value);
    }

    public function getDatatype(): ?Datatype
    {
        return $this->datatype;
    }

    public function getLaravelSQLType(): string
    {
        return 'string';
    }
}
