<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Formularium\Factory\DatatypeFactory;
use Illuminate\Support\Str;
use Modelarium\Datatype\RelationshipFactory;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;
use Modelarium\Parser;

class HasManyDirective implements ModelDirectiveInterface, SeedDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // nothing
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \Formularium\Field $fieldFormularium,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // nothing
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        list($type, $isRequired) = Parser::getUnwrappedType($field->getType());

        $sourceTypeName = $generator->getBaseName();
        $targetTypeName = $type->name;
        $relationship = null;
        $isInverse = false;
        $generateRandom = true; // TODO

        $relationship = RelationshipFactory::RELATIONSHIP_ONE_TO_MANY;
        $isInverse = false;
        $generator->class->addMethod(ModelGenerator::toTableName($targetTypeName))
            ->setPublic()
            ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\HasMany')
            ->setBody("return \$this->hasMany($targetTypeName::class);");
        $datatypeName = $generator->getRelationshipDatatypeName(
            $relationship,
            $isInverse,
            $sourceTypeName,
            $targetTypeName
        );
        return DatatypeFactory::factory($datatypeName);
    }

    public static function processSeedTypeDirective(
        SeedGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // empty
    }

    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $type1 = $generator->getLowerName();
        $type2 = $generator->getInflector()->singularize($field->name);

        if (strcasecmp($type1, $type2) < 0) { // TODO: check this, might not work
            $relationship = lcfirst($generator->getInflector()->pluralize($field->name));
            $generator->extraCode[] = self::makeManyToManySeed($type1, $type2, $relationship);
        }
    }

    protected static function makeManyToManySeed(string $sourceModel, string $targetModel, string $relationship): string
    {
        $className = Str::studly($targetModel);
        return <<<EOF

        try {
            \${$targetModel}Items = App\\Models\\$className::all();
            \$model->{$relationship}()->attach(
                \${$targetModel}Items->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
        catch (\InvalidArgumentException \$e) {
            \$model->{$relationship}()->attach(
                \${$targetModel}Items->random(1)->pluck('id')->toArray()
            );
        }
EOF;
    }
}
