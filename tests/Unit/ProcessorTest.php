<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\GeneratedItem;
use Modelarium\Parser;
use Modelarium\Processor;

final class ProcessorTest extends TestCase
{
    public function testParse()
    {
        $processor = new Processor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToOne')));

        $this->assertEquals(2, $data->count());
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

        $this->assertStringContainsString('$table->unsignedBigInteger("phone_id");', $userMigration->contents);
        $this->assertStringContainsString('$table->foreign("phone_id")->references("id")->on("phones");', $userMigration->contents);

        $this->assertStringContainsString('$table->unsignedBigInteger("user_id");', $userMigration->contents);
        $this->assertStringContainsString('$table->foreign("phone_id")->references("id")->on("phones");', $userMigration->contents);
    }
}
