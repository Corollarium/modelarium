<?php declare(strict_types=1);

namespace ModelariumTests;

use GraphQL\Language\Parser as GParser;

use Modelarium\Parser;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\Parser as LanguageParser;
use GraphQL\Utils\Utils;

class TestScalarType extends \Modelarium\Types\ScalarType
{
    public $name = 'TestScalarType';

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
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        if ($valueNode->value === 'invalid') {
            throw new Error("Not a valid test value", [$valueNode]);
        }
        return $valueNode->value;
    }

    /**
     * Returns the suggested SQL type for this datatype, such as 'TEXT'.
     *
     * @param string $database The database
     * @return string
     */
    public function getSQLType(string $database = '', array $options = []): string
    {
        return 'TEXT';
    }

    /**
     * Returns the suggested Laravel Database type for this datatype.
     *
     * @return string
     */
    public function getLaravelSQLType(string $name, array $options = []): string
    {
        return "text('$name')";
    }
}

final class ScalarExtendTest extends TestCase
{
    public function testCustomScalarParserLoad()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/queryExtendScalar.graphql');
        $this->assertNotNull($parser);
    }

    public function testObjectParserQuery()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/queryExtendScalar.graphql');
        $this->assertNotNull($parser);

        $result = $this->executeQuery(
            'query q($input: TestInputObject) {
            fieldWithObjectInput(input: $input)
        }',
            $parser,
            ['input' =>  [ 't' => 'valid']]
        );

        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithObjectInput' => '{"t":"xvalid"}'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function testCustomScalarParserQuery()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/queryExtendScalar.graphql');
        $this->assertNotNull($parser);

        // executes with complex input:
        $result = $this->executeQuery(
            'query q($input: TestScalarType) {
                fieldWithScalarInput(input: $input)
            }',
            $parser,
            ['input' => 'valid']
        );
        $schema = $parser->getSchema();
        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithScalarInput' => 'xvalid'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function xtestStringParserQuery()
    {
        $document = GParser::parse(
            'query q($input: String) {
            fieldWithNullableStringInput(input: $input)
        }'
        );

        $parser = Parser::fromFile(__DIR__ . '/data/queryExtendScalar.graphql');
        $this->assertNotNull($parser);

        $result = Executor::execute($parser->getSchema(), $document, null, null, ['input' => 'valid']);

        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithNullableStringInput' => '"valid"'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function xxtestObjectScalarParserQuery()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/queryExtendScalar.graphql');
        $this->assertNotNull($parser);
        // executes with complex input:
        $document = GParser::parse(
            'query q($input: TestInputObject) {
            fieldWithObjectInput(input: $input)
        }'
        );

        echo \GraphQL\Utils\SchemaPrinter::doPrint($parser->getSchema());
        $result = Executor::execute($parser->getSchema(), $document, null, null, ['input' => [ 't' => 'valid']]);

        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithObjectInput' => '{"t":"xvalid"}'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    private function executeQuery($query, Parser $parser, $variableValues = null)
    {
        return GraphQL::executeQuery($parser->getSchema(), $query, null, null, $variableValues);
    }
}
