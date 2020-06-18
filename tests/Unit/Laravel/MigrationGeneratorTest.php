<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\MigrationGenerator;
use ModelariumTests\TestCase;

final class MigrationGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new MigrationGenerator('User', $this->getParser('user'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }
}
