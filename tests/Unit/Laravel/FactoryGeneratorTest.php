<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\FactoryGenerator;
use ModelariumTests\TestCase;

final class FactoryGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new FactoryGenerator($this->getParser('user'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
