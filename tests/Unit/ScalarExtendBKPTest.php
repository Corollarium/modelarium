<?php declare(strict_types=1);

namespace ModelariumTests;

use GraphQL\Tests\Executor\TestClasses\ComplexScalar;
use GraphQL\Tests\PHPUnit\ArraySubsetAsserts;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

use GraphQL\Language\Parser as GParser;

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
        var_dump("serial");
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
        var_dump("parse");
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
        var_dump("parsektsl");
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        if ($valueNode->value === 'invalid') {
            throw new Error("Not a valid test value", [$valueNode]);
        }
        return $valueNode->value;
    }
}

final class ScalarExtendBKPTest extends TestCase
{
    public function xxtestCustomScalarParserLoad()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/userExtendScalar.graphql');
        $this->assertNotNull($parser);
    }

    public function xxtestStringInvalidParserQuery()
    {
        $document = GParser::parse(
            'query q($input: String) {
            fieldWithNullableStringInput(input: $input)
        }'
        );

        $result = Executor::execute($this->schema(), $document, null, null, ['input' => 'invalid']);

        $this->assertNotEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithNullableStringInput' => '"valid"'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function xtestStringParserQueryG()
    {
        $document = GParser::parse(
            'query q($input: String) {
            fieldWithNullableStringInput(input: $input)
        }'
        );

        $result = Executor::execute($this->schema(), $document, null, null, ['input' => 'valid']);

        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithNullableStringInput' => '"valid"'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function testObjectParserQuery()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/userExtendScalar.graphql');
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

    public function xtestCustomScalarParserQuery()
    {
        $parser = Parser::fromFile(__DIR__ . '/data/userExtendScalar.graphql');
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
        vaR_dump($result->errors[0]);
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

        $parser = Parser::fromFile(__DIR__ . '/data/userExtendScalar.graphql');
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
        $parser = Parser::fromFile(__DIR__ . '/data/userExtendScalar.graphql');
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

    private function fieldWithInputArg($inputArg)
    {
        return [
            'type'    => Type::string(),
            'args'    => ['input' => $inputArg],
            'resolve' => static function ($_, $args) : ?string {
                if (isset($args['input'])) {
                    return Utils::printSafeJson($args['input']);
                }
                if (array_key_exists('input', $args) && $args['input'] === null) {
                    return 'null';
                }

                return null;
            },
        ];
    }

    public function xxtestScalarParserWithManualSchema()
    {
        $document = GParser::parse(
            'query q($input: TestScalarType) {
            fieldWithScalarInput(input: $input)
        }'
        );

        $result = Executor::execute($this->schema(), $document, null, null, ['input' => 'valid']);

        $this->assertEquals([], $result->errors);
        $expected = [
            'data' => ['fieldWithScalarInput' => '"xvalid"'],
        ];
        $this->assertEquals($expected, $result->toArray());
    }

    public function xxtestScalarParserWithManualSchemaInvalid()
    {
        $document = GParser::parse(
            'query q($input: TestScalarType) {
            fieldWithScalarInput(input: $input)
        }'
        );

        $result = Executor::execute($this->schema(), $document, null, null, ['input' => 'invalid']);

        $this->assertNotEquals([], $result->errors);
    }

    private function executeQuery($query, Parser $parser, $variableValues = null)
    {
        return GraphQL::executeQuery($parser->getSchema(), $query, null, null, $variableValues);
    }

    private function schema()
    {
        $TestScalarType = TestScalarType::create();

        $TestInputObject = new InputObjectType([
            'name'   => 'TestInputObject',
            'fields' => [
                't' => ['type' => $TestScalarType],
            ],
        ]);

        $TestNestedInputObject = new InputObjectType([
            'name'   => 'TestNestedInputObject',
            'fields' => [
                'na' => ['type' => Type::nonNull($TestInputObject)],
                'nb' => ['type' => Type::nonNull(Type::string())],
            ],
        ]);

        $TestEnum = new EnumType([
            'name' => 'TestEnum',
            'values' => [
                'NULL' => [ 'value' => null ],
                'NAN' => [ 'value' => acos(8) ],
                'FALSE' => [ 'value' => false ],
                'CUSTOM' => [ 'value' => 'custom value' ],
                'DEFAULT_VALUE' => [],
            ],
        ]);

        $TestType = new ObjectType([
            'name'   => 'TestType',
            'fields' => [
                'fieldWithEnumInput'              => $this->fieldWithInputArg(['type' => $TestEnum]),
                'fieldWithNonNullableEnumInput'   => $this->fieldWithInputArg(['type' => Type::nonNull($TestEnum)]),
                'fieldWithObjectInput'            => $this->fieldWithInputArg(['type' => $TestInputObject]),
                'fieldWithScalarInput'            => $this->fieldWithInputArg(['type' => $TestScalarType]),
                'fieldWithNullableStringInput'    => $this->fieldWithInputArg(['type' => Type::string()]),
                'fieldWithNonNullableStringInput' => $this->fieldWithInputArg(['type' => Type::nonNull(Type::string())]),
                'fieldWithDefaultArgumentValue'   => $this->fieldWithInputArg([
                    'type'         => Type::string(),
                    'defaultValue' => 'Hello World',
                ]),
                'fieldWithNonNullableStringInputAndDefaultArgumentValue' => $this->fieldWithInputArg([
                    'type' => Type::nonNull(Type::string()),
                    'defaultValue' => 'Hello World',
                ]),
                'fieldWithNestedInputObject'      => $this->fieldWithInputArg([
                    'type'         => $TestNestedInputObject,
                    'defaultValue' => 'Hello World',
                ]),
                'list'                            => $this->fieldWithInputArg(['type' => Type::listOf(Type::string())]),
                'nnList'                          => $this->fieldWithInputArg(['type' => Type::nonNull(Type::listOf(Type::string()))]),
                'listNN'                          => $this->fieldWithInputArg(['type' => Type::listOf(Type::nonNull(Type::string()))]),
                'nnListNN'                        => $this->fieldWithInputArg(['type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string())))]),
            ],
        ]);

        $t = new Schema(['query' => $TestType]);
        // echo \GraphQL\Utils\SchemaPrinter::doPrint($t);
        return $t;
    }
}
