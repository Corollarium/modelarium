<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\MigrationGenerator;
use ModelariumTests\TestCase;

final class MigrationGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new MigrationGenerator('User', $this->getParser('user')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->bigIncrements("id")', $data);
        $this->assertStringContainsString('$table->string("name");', $data);
        $this->assertStringContainsString('$table->string("email");', $data);
    }

    public function testGenerateWithUnique()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userUniqueEmail')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->unique("email");', $data);
    }

    public function testGenerateWithMultiIndex()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userMultiIndex')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->index("name", "surname");', $data);
    }

    public function testGenerateWithSoftDeletes()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userSoftDeletes')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->softDeletes();', $data);
    }

    public function testGenerateWithSpatialIndex()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userSpatialIndex')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->spatialIndex("location");', $data);
    }

    public function testGenerateWithUnsigned()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userUnsigned')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->integer("counter")->unsigned();', $data);
    }

    public function testGenerateWithExtendScalar()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userExtendScalar')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->markTestIncomplete();
        // TODO $this->assertStringContainsString('$table->();', $data);
    }

    public function testNullable()
    {
        $gen = new MigrationGenerator('User', $this->getParser('userNullableField')->getType('User'));
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->string("someField")->nullable();', $data);
        $this->markTestIncomplete();
    }

    public function testOneToOne()
    {
    }
}
