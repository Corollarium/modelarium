<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\Laravel\Targets\EventGenerator;
use ModelariumTests\TestCase;

final class EventGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $gen = new EventGenerator($this->getParser('userEvent'), 'User');
        $data = $gen->generateString();
        $this->assertNotNull($data);
        $this->assertStringContainsString('class UserCreated {', $data);
        $this->assertStringContainsString('namespace app\\Events;', $data);
        $this->markTestIncomplete();
    }
}
