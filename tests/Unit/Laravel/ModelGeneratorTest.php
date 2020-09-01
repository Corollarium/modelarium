<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\ModelGenerator;
use ModelariumTests\TestCase;

final class ModelGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new ModelGenerator($this->getParser('user'), 'User');
        $collection = $gen->generate();
        $data = $collection->first()->contents;

        $this->assertNotNull($data);
    }

    public function testGenerateWithSoftDeletes()
    {
        $gen = new ModelGenerator($this->getParser('userBaseDirectives'), 'User');
        $collection = $gen->generate();
        $data = $collection->first()->contents;
        $this->assertNotNull($data);
        $this->assertStringContainsString('use \Illuminate\Database\Eloquent\SoftDeletes;', $data);
    }
}
