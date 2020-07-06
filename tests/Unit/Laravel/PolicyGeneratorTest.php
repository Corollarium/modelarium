<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\Laravel\Targets\PolicyGenerator;
use ModelariumTests\TestCase;

final class PolicyGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $parser = $this->getParser('userPolicy');
        $gen = new PolicyGenerator($parser, 'Mutation', $parser->getSchema()->getMutationType());
        $data = $gen->generate();
        $this->assertNotNull($data);
        $this->assertEquals(1, $data->count());
        $contents = $data->first()->contents;

        $this->assertStringContainsString('class PostPolicy', $contents);
        $this->assertStringContainsString('public function create(User $user): bool', $contents);
        $this->assertStringContainsString('public function update(User $user, Post $model): bool', $contents);
        $this->assertStringContainsString('public function arg(User $user, array $staticArgs): bool', $contents);
        $this->assertStringContainsString('public function inject(User $user, array $injectedArgs): bool', $contents);
        $this->assertStringContainsString('public function argInject(User $user, array $injectedArgs, array $staticArgs): bool', $contents);
    }
}
