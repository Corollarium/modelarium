<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\MigrationGenerator;
use ModelariumTests\TestCase;

class ScalarTestText extends \Modelarium\Types\ScalarType
{
    public $name = 'ScalarTestText';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param string $value
     * @return string
     */
    public function serialize($value)
    {
        return $this->parseValue($value);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        return $value;
    }

    /**
     * Returns the suggested SQL type for this datatype, such as 'TEXT'.
     *
     * @param string $database The database
     * @return string
     */
    public function getSQLType(string $database = '', array $options = []): string
    {
        return 'TEXT';
    }

    /**
     * Returns the suggested Laravel Database type for this datatype.
     *
     * @return string
     */
    public function getLaravelSQLType(string $name, array $options = []): string
    {
        return "text(\"$name\")";
    }
}

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
        $gen = new MigrationGenerator($this->getParser('userBaseDirectives'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->softDeletes();', $data);
    }

    public function testGenerateWithTimestamps()
    {
        $gen = new MigrationGenerator($this->getParser('userBaseDirectives'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->timestamps();', $data);
    }

    public function testGenerateWithRememberToken()
    {
        $gen = new MigrationGenerator($this->getParser('userBaseDirectives'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->rememberToken();', $data);
    }

    public function testGenerateWithSpatialIndex()
    {
        $gen = new MigrationGenerator($this->getParser('userSpatialIndex'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->spatialIndex("location");', $data);
    }

    public function testGenerateWithFulltextIndex()
    {
        $gen = new MigrationGenerator($this->getParser('userFullTextIndex'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString(
            'DB::statement(\'ALTER TABLE users ADD FULLTEXT fulltext_index ("name", "description")\');',
            $data
        );
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
    }

    public function testBaseTypes()
    {
        $gen = new MigrationGenerator($this->getParser('userBaseTypes'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->integer("ainteger");', $data);
        $this->assertStringContainsString('$table->float("afloat");', $data);
        $this->assertStringContainsString('$table->string("astring");', $data);
        $this->assertStringContainsString('$table->boolean("aboolean");', $data);
    }

    public function testExtendedTypes()
    {
        $gen = new MigrationGenerator($this->getParser('userExtendedScalar'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->text("description");', $data);
    }

    public function testFormulariumExtendedTypes()
    {
        $gen = new MigrationGenerator($this->getParser('userFormulariumScalar'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('$table->year(\'year\');', $data);
    }

    public function testEnums()
    {
        $this->markTestIncomplete();
        // TODO: assert enum type files created
    }
}
