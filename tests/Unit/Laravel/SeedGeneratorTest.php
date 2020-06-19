<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\SeedGenerator;
use ModelariumTests\TestCase;

final class SeedGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new SeedGenerator($this->getParser('user'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
