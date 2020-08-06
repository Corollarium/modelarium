<?php declare(strict_types=1);

namespace ModelariumTests\Laravel;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Laravel\Processor as LaravelProcessor;
use ModelariumTests\TestCase;

final class ProcessorTest extends TestCase
{
    public function testParseRelationshipOneToOne()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToOne')));
        $this->_checkOneToMany($data);
    }

    protected function _checkOneToMany(GeneratedCollection $data)
    {
        $this->assertNotNull($data);
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
        $this->assertStringContainsString('return $this->hasOne(\\App\\Models\\Phone::class);', $userModel->contents);
        $this->assertStringContainsString('public function user()', $phoneModel->contents);
        $this->assertStringContainsString('return $this->belongsTo(\\App\\Models\\User::class);', $phoneModel->contents);
    }

    public function testParseRelationshipOneToOneNullable()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToOneNullable')));

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
    }

    public function testParseRelationshipOneToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('oneToMany')));

        $postMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'post') > 0;
            }
        )->first();

        $commentMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'comment') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('comment_id', $postMigration->contents);
        $this->assertStringNotContainsString('->references;', $postMigration->contents);

        $this->assertStringContainsString('$table->unsignedBigInteger("post_id");', $commentMigration->contents);
        $this->assertStringContainsString(
            '$table->foreign("post_id")->references("id")->on("posts")->onDelete("cascade")->onUpdate("cascade");',
            $commentMigration->contents
        );

        $postModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'post') > 0;
            }
        )->first();

        $commentModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'comment') > 0;
            }
        )->first();

        $this->assertStringContainsString('public function comments()', $postModel->contents);
        $this->assertStringContainsString('return $this->hasMany(\\App\\Models\\Comment::class);', $postModel->contents);
        $this->assertStringContainsString('public function post()', $commentModel->contents);
        $this->assertStringContainsString('return $this->belongsTo(\\App\\Models\\Post::class);', $commentModel->contents);
    }

    public function testParseRelationshipManyToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('manyToMany')));

        $this->assertEquals(3, $data->filterByType(GeneratedItem::TYPE_MIGRATION)->count());
        $roleToUser = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    stripos($i->filename, 'role_user') > 0;
            }
        )->first();

        $userMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'create_user_table') > 0;
            }
        )->first();

        $roleMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'role') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('$table->unsignedBigInteger(', $userMigration->contents);
        $this->assertStringNotContainsString('$table->unsignedBigInteger(', $roleMigration->contents);
        $this->assertStringContainsString('$table->unsignedBigInteger("user_id")->references(\'id\')->on(\'user\')', $roleToUser->contents);
        $this->assertStringContainsString('$table->unsignedBigInteger("role_id")->references(\'id\')->on(\'role\');', $roleToUser->contents);


        $userModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'user') > 0;
            }
        )->first();

        $roleModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'role') > 0;
            }
        )->first();

        $this->assertStringContainsString('public function roles()', $userModel->contents);
        $this->assertStringContainsString('return $this->belongsToMany(\\App\\Models\\Role::class);', $userModel->contents);
        $this->assertStringContainsString('public function users()', $roleModel->contents);
        $this->assertStringContainsString('return $this->belongsToMany(\\App\\Models\\User::class);', $roleModel->contents);
    }

    public function testSplitStrings()
    {
        $strings = [
            LaravelProcessor::getDirectivesGraphqlString(),
            \Safe\file_get_contents(__DIR__ . '/../data/lighthouse-schema-directives.graphql')
        ];

        $strings[] = <<< EOF
type User {
    id: ID!
    phone: Phone! @hasOne
}

type Query {
    users: [User!]! @paginate(defaultCount: 10)
    user(id: ID @eq): User @find
}
EOF;

        $strings[] = <<< EOF
type Phone {
    id: ID!
    user: User! @belongsTo @migrationForeign(onDelete: "cascade", onUpdate: "cascade")
}

type Query {
    phones: [User!]! @paginate(defaultCount: 10)
    phone(id: ID @eq): Phone @find
}
EOF;

        $processor = new LaravelProcessor();
        $data = $processor->processStrings($strings);
        $this->_checkOneToMany($data);
    }

    public function testParseRelationshipMorphOneToOne()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('morphOneToOne')));
        $this->assertNotNull($data);

        $userMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'user') > 0;
            }
        )->first();

        $imageMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'image') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('image_id', $userMigration->contents);
        $this->assertStringNotContainsString('->references;', $userMigration->contents);

        $this->assertContains('$table->bigIncrements("id");', $imageMigration->contents);
        $this->assertContains('unsignedBigInteger("imageable_id")', $imageMigration->contents);
        $this->assertContains('string("imageable_type")', $imageMigration->contents);

        $userModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'user') > 0;
            }
        )->first();

        $imageModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'image') > 0;
            }
        )->first();

        $this->assertContains('public function image()', $userModel->contents);
        $this->assertContains('return $this->morphOne(Image::class, \'imageable\');', $userModel->contents);

        $this->assertContains('public function imageable()', $imageModel->contents);
        $this->assertContains('return $this->morphTo();', $imageModel->contents);
    }

    public function testParseRelationshipMorphOneToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('morphOneToMany')));
        $this->assertNotNull($data);

        $userMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'user') > 0;
            }
        )->first();

        $imageMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'image') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('image_id', $userMigration->contents);
        $this->assertStringNotContainsString('->references;', $userMigration->contents);

        $this->assertContains('$table->bigIncrements("id");', $imageMigration->contents);
        $this->assertContains('unsignedBigInteger("imageable_id")', $imageMigration->contents);
        $this->assertContains('string("imageable_type")', $imageMigration->contents);

        $userModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'user') > 0;
            }
        )->first();

        $imageModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'image') > 0;
            }
        )->first();

        $this->assertContains('public function images()', $userModel->contents);
        $this->assertContains('return $this->morphMany(Image::class, \'imageable\');', $userModel->contents);

        $this->assertContains('public function imageable()', $imageModel->contents);
        $this->assertContains('return $this->morphTo();', $imageModel->contents);
    }

    public function testParseRelationshipMorphManyToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processString(file_get_contents($this->getPathGraphql('morphManyToMany')));
        $this->assertNotNull($data);

        $postMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'post') > 0;
            }
        )->first();

        $tagMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'tag_') > 0;
            }
        )->first();

        $taggablesMigration = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MIGRATION &&
                    strpos($i->filename, 'taggable') > 0;
            }
        )->first();

        $this->assertStringNotContainsString('tag_id', $postMigration->contents);
        $this->assertStringNotContainsString('->references;', $postMigration->contents);

        $this->assertContains('$table->bigIncrements("id");', $tagMigration->contents);
        $this->assertContains('string("name")', $tagMigration->contents);

        $this->assertStringNotContainsString('increments', $taggablesMigration->contents);
        $this->assertContains("create('taggables'", $taggablesMigration->contents);
        $this->assertContains('unsignedBigInteger("tag_id")', $taggablesMigration->contents);
        $this->assertContains('unsignedBigInteger("taggable_id")', $taggablesMigration->contents);
        $this->assertContains('string("taggable_type")', $taggablesMigration->contents);

        $postModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'post') > 0;
            }
        )->first();

        $tagModel = $data->filter(
            function (GeneratedItem $i) {
                return $i->type == GeneratedItem::TYPE_MODEL &&
                    stripos($i->filename, 'tag') > 0;
            }
        )->first();

        $this->assertContains('public function tags()', $postModel->contents);
        $this->assertContains('return $this->morphToMany(Tag::class, \'taggable\');', $postModel->contents);

        $this->assertContains('public function posts()', $tagModel->contents);
        $this->assertContains('public function videos()', $tagModel->contents);
        $this->assertContains('return $this->morphedByMany(Post::class, \'taggable\');', $tagModel->contents);
        $this->assertContains('return $this->morphedByMany(Video::class, \'taggable\');', $tagModel->contents);
    }
}
