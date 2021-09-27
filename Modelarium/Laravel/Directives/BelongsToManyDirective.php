<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Formularium\Factory\DatatypeFactory;
use Illuminate\Support\Str;
use Modelarium\Datatypes\RelationshipFactory;
use Modelarium\Exception\DirectiveException;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Laravel\Targets\MigrationGenerator;

class BelongsToManyDirective implements MigrationDirectiveInterface, ModelDirectiveInterface, SeedDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        throw new DirectiveException("Directive not supported here");
    }

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        throw new DirectiveException("Directive not supported here");
    }

    public static function processMigrationRelationshipDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        $type1 = $generator->getLowerFirstLetterName();
        $type2 = lcfirst($generator->getInflector()->singularize($field->name));

        // we only generate once, so use a comparison for that
        if (strcasecmp($type1, $type2) < 0) {
            $generator->generateManyToManyTable($type1, $type2);
        }
    }

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
        $lowerName = lcfirst($generator->getInflector()->singularize($field->name));
        $lowerNamePlural = lcfirst($generator->getInflector()->pluralize($lowerName));

        $sourceTypeName = $generator->getBaseName();
        $targetTypeName = ucfirst($lowerName);
        $relationship = null;
        $isInverse = false;

        $targetClass = Str::studly($generator->getInflector()->singularize($field->name));
        $generateRandom = true; // TODO
        $relationship = RelationshipFactory::RELATIONSHIP_MANY_TO_MANY;
        $isInverse = true;
        $generator->class->addMethod($lowerNamePlural)
            ->setPublic()
            ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany')
            ->setBody("return \$this->belongsToMany($targetClass::class);");
        
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
    }

    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $type1 = $generator->getLowerName();
        $type2 = lcfirst($generator->getInflector()->singularize($field->name));

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
