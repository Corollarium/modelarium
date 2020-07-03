<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Parser;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getPathGraphql($name)
    {
        return __DIR__ . '/data/' . $name . '.graphql';
    }

    protected function getParser($name)
    {
        return (new Parser())->fromFile($this->getPathGraphql($name));
    }
}
