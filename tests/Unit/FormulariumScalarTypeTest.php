<?php declare(strict_types=1);

namespace ModelariumTests;

use Formularium\Datatype;
use Modelarium\Laravel\Processor;
use Modelarium\Parser;
use Modelarium\Types\FormulariumScalarType;

final class FormulariumScalarTypeTest extends TestCase
{
    public function testGet()
    {
        $parser = (new Parser())->fromFile(__DIR__ . '/data/userFormulariumScalar.graphql');
        /**
         * @var FormulariumScalarType $scalarType
         */
        $scalarType = $parser->getScalarType('Year');
        $this->assertInstanceOf(FormulariumScalarType::class, $scalarType);
        $this->assertInstanceOf(Datatype::class, $scalarType->getDatatype());
        $this->assertContains('INT', $scalarType->getSQLType());
        $this->assertContains('year(', $scalarType->getLaravelSQLType('x'));
        $this->assertContains('xxxx', $scalarType->getLaravelSQLType('xxxx'));
    }
}
