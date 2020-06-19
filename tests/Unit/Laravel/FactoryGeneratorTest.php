<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\FactoryGenerator;
use ModelariumTests\TestCase;

final class FactoryGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new FactoryGenerator('User', $this->getParser('user')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
