<?php declare(strict_types=1);

use Modelarium\Laravel\Targets\SeedGenerator;
use PHPUnit\Framework\TestCase;

final class SeedGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new SeedGenerator('User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
