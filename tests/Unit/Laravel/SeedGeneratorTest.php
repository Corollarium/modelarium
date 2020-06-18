<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Laravel\Targets\SeedGenerator;
use ModelariumTests\TestCase;

final class SeedGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new SeedGenerator('User', $this->getParser('user'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
