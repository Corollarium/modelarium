<?php declare(strict_types=1);

namespace Modelarium\Types;

use Formularium\CodeGenerator\GraphQL\CodeGenerator as GraphQLCodeGenerator;
use Formularium\Datatype;
use Formularium\Factory\DatatypeFactory;
use Formularium\Exception\ValidatorException;
use Formularium\Field;
use GraphQL\Error\Error;

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
        $name = str_replace('Datatype_', '', $this->name);
        $this->datatype = DatatypeFactory::factory($name);
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
        // $field = new Field(); // TODO
        // return $this->datatype->format($value);
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        try {
            return $this->datatype->validate($value);
        } catch (ValidatorException $e) {
            throw new Error($e->getMessage());
        }
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
     * @throws \Throwable
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        return $this->parseValue($valueNode->value); /** @phpstan-ignore-line */
    }

    /**
     * Returns the Graphql query for this datatype.
     *
     * @return string
     */
    public function getGraphqlType(): string
    {
        $scg = new GraphQLCodeGenerator();
        return $scg->datatypeDeclaration($this->datatype); // TODO: studlycase?
    }
}
