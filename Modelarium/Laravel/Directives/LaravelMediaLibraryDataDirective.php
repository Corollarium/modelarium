<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Illuminate\Support\Str;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;

class LaravelMediaLibraryDataDirective implements ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        $collection = 'images';
        $customFields = [];
        $studlyFieldName = Str::studly($field->name);

        // deps
        if (!in_array('\\Spatie\\MediaLibrary\\HasMedia', $generator->class->getImplements())) {
            $generator->class->addImplement('\\Spatie\\MediaLibrary\\HasMedia');
            $generator->class->addTrait('\\Spatie\\MediaLibrary\\InteractsWithMedia');
        }

        // args
        foreach ($directive->arguments as $arg) {
            /**
             * @var \GraphQL\Language\AST\ArgumentNode $arg
             */

            switch ($arg->name->value) {
                case 'collection':
                    /** @phpstan-ignore-next-line */
                    $collection = $arg->value->value;
                break;
                case 'fields':
                    /** @phpstan-ignore-next-line */
                    foreach ($arg->value->values as $item) {
                        $customFields[] = $item->value;
                    }
                break;
                }
        }
        $studlyCollection = Str::studly($collection);

        // registration
        if (!$generator->class->hasMethod("registerMediaCollections")) {
            $registerMediaCollections = $generator->class->addMethod("registerMediaCollections")
                    ->setPublic()
                    ->setReturnType('void')
                    ->addComment("Configures Laravel media-library");
        } else {
            $registerMediaCollections = $generator->class->getMethod("registerMediaCollections");
        }
        $registerMediaCollections->addBody("\$generator->addMediaCollection(?);\n", [$collection]);

        // all image models for this collection
        $generator->class->addMethod("getMedia{$studlyCollection}Collection")
                ->setPublic()
                ->setReturnType('\\Spatie\\MediaLibrary\\MediaCollections\\Models\\Collections\\MediaCollection')
                ->addComment("Returns a collection media from Laravel-MediaLibrary")
                ->setBody("return \$generator->getMedia(?);", [$collection]);

        // custom fields
        $generator->class->addMethod("getMedia{$studlyCollection}CustomFields")
                ->setPublic()
                ->setReturnType('array')
                ->addComment("Returns custom fields for the media")
                ->setBody("return ?;", [$customFields]);

        $generator->class->addMethod("get{$studlyFieldName}urlAttribute")
                ->setPublic()
                ->setReturnType('string')
                ->addComment("Returns the media attribute (url) for the $collection")
                ->setBody( /** @lang PHP */
                    <<< PHP
    \$image = \$generator->getMedia{$studlyCollection}Collection()->first();
    if (\$image) {
        return \$image->getUrl();
    }
    return '';
    PHP
                );

        // all image models for this collection
        $generator->class->addMethod("get{$studlyFieldName}Attribute")
                ->setPublic()
                ->setReturnType('array')
                ->addComment("Returns media attribute for the $collection media with custom fields")
                ->setBody( /** @lang PHP */
                    <<< PHP
    \$image = \$generator->getMedia{$studlyCollection}Collection()->first();
if (\$image) {
\$customFields = [];
foreach (\$generator->getMedia{$studlyCollection}CustomFields() as \$c) {
    \$customFields[\$c] = \$image->getCustomProperty(\$c);
}
return [
    'url' => \$image->getUrl(),
    'fields' => json_encode(\$customFields)
];
}
return [];
PHP
                );
        return '';
    }
}
