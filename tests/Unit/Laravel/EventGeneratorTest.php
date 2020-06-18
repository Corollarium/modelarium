<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Laravel\Targets\EventGenerator;
use ModelariumTests\TestCase;

final class EventGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new EventGenerator('User', $this->getParser('userEvent'));
        // $data = $gen->generateString();
        // $this->assertNotNull($data);
        $this->markTestIncomplete();
    }
}
