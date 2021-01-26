<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Exception\ScalarNotFoundException;
use Modelarium\Parser;
use LightnCandy\LightnCandy;

final class ParserTest extends TestCase
{
    public function testParse()
    {
        $parser = (new Parser())->fromString(
            <<<EOF
type Query {
    users: [User!]!
    user(id: ID): User
}

type User {
    id: ID!
    name: String!
    email: String!
}
EOF
        );
        $this->assertNotNull($parser);
    }

    public function testParseFromFile()
    {
        $parser = (new Parser())->fromFile(__DIR__ . '/data/userQueryInput.graphql');
        $this->assertNotNull($parser);
    }

    public function testScalars()
    {
        $parser = new Parser();
        $scalars = $parser->getScalars();
        $this->assertArrayHasKey('String', $scalars);
    }

    public function testScalarType()
    {
        $parser = new Parser();
        $this->assertNull($parser->getScalarType("kasdfmaiooiwer"));
    }
}
