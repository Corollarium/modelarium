<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\ModelGenerator;
use ModelariumTests\TestCase;

final class ModelGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new ModelGenerator($this->getParser('user'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
    }

    public function testGenerateWithSoftDeletes()
    {
        $gen = new ModelGenerator($this->getParser('userBaseDirectives'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('use \Illuminate\Database\Eloquent\SoftDeletes;', $data);
    }
}
