<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\EventGenerator;
use ModelariumTests\TestCase;

final class EventGeneratorTest extends TestCase
{
    public function testGenerateMutation()
    {
        $parser = $this->getParser('userEvent');
        $gen = new EventGenerator($parser, 'Mutation', $parser->getSchema()->getMutationType());
        $data = $gen->generate();
        $this->assertNotNull($data);
        $this->assertEquals(1, $data->count());
        $event = $data->first();
        $this->assertStringContainsString('class UserCreated', $event->contents);
        $this->assertStringContainsString('namespace App\\Events;', $event->contents);
        $this->assertStringContainsString('public function __construct(\\App\\Models\\User $target)', $event->contents);
    }

    public function testGenerateQuery()
    {
        $parser = $this->getParser('userEvent');
        $gen = new EventGenerator($parser, 'Query', $parser->getSchema()->getQueryType());
        $data = $gen->generate();
        $this->assertNotNull($data);
        $this->assertEquals(1, $data->count());
        $event = $data->first();
        $this->assertStringContainsString('class UserViewed', $event->contents);
        $this->assertStringContainsString('namespace App\\Events;', $event->contents);
        $this->assertStringContainsString('public function __construct(\\App\\Models\\User $target)', $event->contents);
    }
}
