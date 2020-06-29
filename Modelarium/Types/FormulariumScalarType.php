<?php declare(strict_types=1);

namespace Modelarium\Types;

use Formularium\Datatype;

abstract class FormulariumScalarType extends ScalarType
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
        $this->datatype = Datatype::factory(str_replace('Datatype_', '', $this->name));
    }

    public function getDatatype(): Datatype
    {
        return $this->datatype;
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

    /**
     * Returns the suggested SQL type for this datatype, such as 'TEXT'.
     *
     * @param string $database The database
     * @return string
     */
    public function getSQLType(string $database = '', array $options = []): string
    {
        return $this->datatype->getSQLType($database, $options);
    }

    /**
     * Returns the suggested Laravel Database type for this datatype.
     *
     * @return string
     */
    public function getLaravelSQLType(string $name, array $options = []): string
    {
        return $this->datatype->getLaravelSQLType($name, $options);
    }
}
