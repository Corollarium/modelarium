<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Parser;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getParser($name)
    {
        return Parser::fromPath(__DIR__ . '/data/' . $name . '.graphql');
    }
}
