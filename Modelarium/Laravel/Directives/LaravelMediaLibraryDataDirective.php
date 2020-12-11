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

        $conversion = '';
        $width = 200;
        $height = 200;
        $responsive = false;

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
                case 'conversion':
                    /** @phpstan-ignore-next-line */
                    $conversion = $arg->value->value;
                break;
                case 'width':
                    /** @phpstan-ignore-next-line */
                    $width = $arg->value->value;
                break;
                case 'height':
                    /** @phpstan-ignore-next-line */
                    $height = $arg->value->value;
                break;
                case 'responsive':
                    /** @phpstan-ignore-next-line */
                    $responsive = $arg->value->value;
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
        $registerMediaCollections->addBody("\$this->addMediaCollection(?);\n", [$collection]);

        // conversion
        if ($conversion) {
            if (!$generator->class->hasMethod("registerMediaConversions")) {
                $registerMediaConversions = $generator->class->addMethod("registerMediaConversions")
                    ->setPublic()
                    ->setReturnType('void')
                    ->addComment("Configures Laravel media-library conversions");
                $registerMediaConversions->addParameter('media')
                    ->setDefaultValue(null)
                    ->setType('\\Spatie\\MediaLibrary\\MediaCollections\\Models\\Media')
                    ->setNullable(true);
            } else {
                $registerMediaConversions = $generator->class->getMethod("registerMediaConversions");
            }
            $registerMediaConversions->addBody(
                "\$this->addMediaConversions(?)" .
                    ($width ? '->width(?)' : '') .
                    ($height ? '->height(?)' : '') .
                    ($responsive ? '->withResponsiveImages()' : '') .
                ";\n",
                array_merge([$conversion], ($width ? [$width] : []), ($height ? [$height] : []))
            );
        }

        // all image models for this collection
        $generator->class->addMethod("getMedia{$studlyCollection}Collection")
                ->setPublic()
                ->setReturnType('\\Spatie\\MediaLibrary\\MediaCollections\\Models\\Collections\\MediaCollection')
                ->addComment("Returns a collection media from Laravel-MediaLibrary")
                ->setBody("return \$this->getMedia(?);", [$collection]);

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
    \$image = \$this->getMedia{$studlyCollection}Collection()->first();
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
    \$image = \$this->getMedia{$studlyCollection}Collection()->first();
if (\$image) {
\$customFields = [];
foreach (\$this->getMedia{$studlyCollection}CustomFields() as \$c) {
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

        // TODO: get converted images, thumb https://spatie.be/docs/laravel-medialibrary/v8/converting-images/retrieving-converted-images
        return '';
    }
}
