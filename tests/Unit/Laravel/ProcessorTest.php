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
        $data = $processor->processFiles([$this->getPathGraphql('oneToOne')]);
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
        $this->assertStringContainsString('return $this->hasOne(Phone::class);', $userModel->contents);
        $this->assertStringContainsString('public function user()', $phoneModel->contents);
        $this->assertStringContainsString('return $this->belongsTo(User::class);', $phoneModel->contents);
    }

    public function testParseRelationshipOneToOneNullable()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processFiles([$this->getPathGraphql('oneToOneNullable')]);

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
        $data = $processor->processFiles([$this->getPathGraphql('oneToMany')]);

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
        $this->assertStringContainsString('return $this->hasMany(Comment::class);', $postModel->contents);
        $this->assertStringContainsString('public function post()', $commentModel->contents);
        $this->assertStringContainsString('return $this->belongsTo(Post::class);', $commentModel->contents);
    }

    public function testParseRelationshipManyToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processFiles([$this->getPathGraphql('manyToMany')]);

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
        $this->assertStringContainsString('return $this->belongsToMany(Role::class);', $userModel->contents);
        $this->assertStringContainsString('public function users()', $roleModel->contents);
        $this->assertStringContainsString('return $this->belongsToMany(User::class);', $roleModel->contents);
    }

    public function testSplitStrings()
    {
        $strings = [
            LaravelProcessor::getDirectivesGraphqlString(),
            \Safe\file_get_contents(__DIR__ . '/../../../Modelarium/Laravel/Graphql/definitionsLighthouse.graphql')
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
        $data = $processor->processFiles([$this->getPathGraphql('morphOneToOne')]);
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

        $this->assertStringContainsString('$table->bigIncrements("id");', $imageMigration->contents);
        $this->assertStringContainsString('unsignedBigInteger("imageable_id")', $imageMigration->contents);
        $this->assertStringContainsString('string("imageable_type")', $imageMigration->contents);

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

        $this->assertStringContainsString('public function image()', $userModel->contents);
        $this->assertStringContainsString('return $this->morphOne(Image::class, \'imageable\');', $userModel->contents);

        $this->assertStringContainsString('public function imageable()', $imageModel->contents);
        $this->assertStringContainsString('return $this->morphTo();', $imageModel->contents);
    }

    public function testParseRelationshipMorphOneToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processFiles([$this->getPathGraphql('morphOneToMany')]);
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

        $this->assertStringContainsString('$table->bigIncrements("id");', $imageMigration->contents);
        $this->assertStringContainsString('unsignedBigInteger("imageable_id")', $imageMigration->contents);
        $this->assertStringContainsString('string("imageable_type")', $imageMigration->contents);

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

        $this->assertStringContainsString('public function images()', $userModel->contents);
        $this->assertStringContainsString('return $this->morphMany(Image::class, \'imageable\');', $userModel->contents);

        $this->assertStringContainsString('public function imageable()', $imageModel->contents);
        $this->assertStringContainsString('return $this->morphTo();', $imageModel->contents);
    }

    public function testParseRelationshipMorphManyToMany()
    {
        $processor = new LaravelProcessor();
        $data = $processor->processFiles([$this->getPathGraphql('morphManyToMany')]);
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

        $this->assertStringContainsString('$table->bigIncrements("id");', $tagMigration->contents);
        $this->assertStringContainsString('string("name")', $tagMigration->contents);

        $this->assertStringNotContainsString('increments', $taggablesMigration->contents);
        $this->assertStringContainsString("create('taggables'", $taggablesMigration->contents);
        $this->assertStringContainsString('unsignedBigInteger("tag_id")', $taggablesMigration->contents);
        $this->assertStringContainsString('unsignedBigInteger("taggable_id")', $taggablesMigration->contents);
        $this->assertStringContainsString('string("taggable_type")', $taggablesMigration->contents);

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

        $this->assertStringContainsString('public function tags()', $postModel->contents);
        $this->assertStringContainsString('return $this->morphToMany(Tag::class, \'taggable\');', $postModel->contents);

        $this->assertStringContainsString('public function posts()', $tagModel->contents);
        $this->assertStringContainsString('public function videos()', $tagModel->contents);
        $this->assertStringContainsString('return $this->morphedByMany(Post::class, \'taggable\');', $tagModel->contents);
        $this->assertStringContainsString('return $this->morphedByMany(Video::class, \'taggable\');', $tagModel->contents);
    }
}
