<?php declare(strict_types=1);

use Modelarium\Laravel\Targets\FactoryGenerator;
use PHPUnit\Framework\TestCase;

final class FactoryGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new FactoryGenerator('User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
