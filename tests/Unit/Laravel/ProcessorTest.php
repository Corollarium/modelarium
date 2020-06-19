<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
use Modelarium\Parser;
use Modelarium\Processor;

final class LaravelProcessorTest extends TestCase
{
    public function testParseRelationshipOneToOne()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToOne')));

        $userMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type = GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'user') > 0;
            }
        )->first();

        $phoneMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type = GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'phone') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('phone_id', $userMigration->contents);
        $this->assertStringNotContainsString('->references;', $userMigration->contents);

        $this->assertStringContainsString('$table->unsignedBigInteger("user_id");', $phoneMigration->contents);
        $this->assertStringContainsString(
            '$table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->onUpdate("cascade");',
            $phoneMigration->contents
        );
    }
}
