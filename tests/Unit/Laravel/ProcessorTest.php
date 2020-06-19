<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
use ModelariumTests\TestCase;

final class LaravelProcessorTest extends TestCase
{
    public function testParseRelationshipOneToOne()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToOne')));

        $userMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'user') > 0;
            }
        )->first();

        $phoneMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
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

        $userModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'user') > 0;
            }
        )->first();

        $phoneModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'phone') > 0;
            }
        )->first();

        $this->assertStringContainsString('public function phone()', $userModel->contents);
        $this->assertStringContainsString('return $this->hasOne(App\\\\Phone::class);', $userModel->contents);
        $this->assertStringContainsString('public function user()', $phoneModel->contents);
        $this->assertStringContainsString('return $this->belongsTo(App\\\\User::class);', $phoneModel->contents);
    }
}
