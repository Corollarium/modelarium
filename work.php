<?php declare(strict_types=1);

namespace ModelariumTests;

require('vendor/autoload.php');

use Modelarium\Parser;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Parser as LanguageParser;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class TestScalarType extends ScalarType
{
    public $name = 'TestScalarType';

    public static function create() : self
    {
        return new self();
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @param string $value
     * @return string
     */
    public function serialize($value)
    {
        return $this->parseValue($value);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        // invalid value checker
        if ($value === 'invalid') {
            throw new InvariantViolation("Could not serialize test value: " . Utils::printSafe($value));
        }

        return "x" . $value;
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
        var_dump("parselit");
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        if ($valueNode->value === 'invalid') {
            throw new Error("Not a valid test value", [$valueNode]);
        }
        return $valueNode->value;
    }
}

$parser = Parser::fromFile(__DIR__ . '/tests/Unit/data/userExtendScalar.graphql');
// executes with complex input:

$schema = $parser->getSchema();
// $schema->resolvedTypes['TestScalarType'] = new TestScalarType();
$result = GraphQL::executeQuery(
    $schema,
    'query q($input:TestScalarType) {
        fieldWithScalarInput(input: $input)
    }',
    null,
    null,
    ['input' => 'valid']
);
var_dump($result);
