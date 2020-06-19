<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\MigrationGenerator;
use ModelariumTests\TestCase;

final class MigrationGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new MigrationGenerator($this->getParser('user'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->bigIncrements("id")', $data);
        $this->assertStringContainsString('$table->string("name");', $data);
        $this->assertStringContainsString('$table->string("email");', $data);
    }

    public function testGenerateWithUnique()
    {
        $gen = new MigrationGenerator($this->getParser('userUniqueEmail'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->unique("email");', $data);
    }

    public function testGenerateWithMultiIndex()
    {
        $gen = new MigrationGenerator($this->getParser('userMultiIndex'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->index("name", "surname");', $data);
    }

    public function testGenerateWithSoftDeletes()
    {
        $gen = new MigrationGenerator($this->getParser('userSoftDeletes'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->softDeletes();', $data);
    }

    public function testGenerateWithSpatialIndex()
    {
        $gen = new MigrationGenerator($this->getParser('userSpatialIndex'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->spatialIndex("location");', $data);
    }

    public function testGenerateWithUnsigned()
    {
        $gen = new MigrationGenerator($this->getParser('userUnsigned'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->integer("counter")->unsigned();', $data);
    }

    public function testGenerateWithExtendScalar()
    {
        // $gen = new MigrationGenerator($this->getParser('userExtendScalar'), 'User');
        // $data = $gen->generateString();
        // $this->assertNotNull($data);
        $this->markTestIncomplete();
        // TODO $this->assertStringContainsString('$table->();', $data);
    }

    public function testNullable()
    {
        $gen = new MigrationGenerator($this->getParser('userNullableField'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->string("someField")->nullable();', $data);
        $this->markTestIncomplete();
    }

    public function testOneToOne()
    {
    }
}
