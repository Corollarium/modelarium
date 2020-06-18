<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Parser;

final class ParserTest extends TestCase
{
    public function testParse()
    {
        $parser = Parser::fromString(
            <<<EOF
type Query {
    users: [User!]! @paginate(defaultCount: 10)
    user(id: ID @eq): User @find
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
}
