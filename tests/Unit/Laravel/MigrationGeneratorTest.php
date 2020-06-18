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
        $this->assertStringContainsString('$table->bigIncrements("id")', $data);
        $this->assertStringContainsString('$table->string("name");', $data);
        $this->assertStringContainsString('$table->string("email");', $data);
    }

    public function testGenerateWithUnique()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userUniqueEmail'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->unique("email");', $data);
    }

    public function testGenerateWithMultiIndex()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userMultiIndex'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->index("name", "surname");', $data);
    }

    public function testGenerateWithSoftDeletes()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userSoftDeletes'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->softDeletes();', $data);
    }
}
