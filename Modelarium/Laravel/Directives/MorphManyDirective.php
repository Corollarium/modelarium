<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Formularium\Factory\DatatypeFactory;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Parser;
use Modelarium\Datatypes\RelationshipFactory;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;

class MorphManyDirective implements ModelDirectiveInterface, SeedDirectiveInterface
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
        $name = $directive->name->value;
        list($type, $isRequired) = Parser::getUnwrappedType($field->getType());
        $typeName = $type->name;

        $lowerName = lcfirst($generator->getInflector()->singularize($field->name));
        $lowerNamePlural = $generator->getInflector()->pluralize($lowerName);

        $sourceTypeName = $generator->getLowerName();
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;
        $targetClass = '\\App\\Models\\' . Str::studly($generator->getInflector()->singularize($field->name));
        $generateRandom = true; // TODO

        $relationship = RelationshipFactory::MORPH_ONE_TO_MANY;
        $isInverse = false;

        $targetType = $generator->parser->getType($typeName);
        if (!$targetType) {
            throw new Exception("Cannot get type {$typeName} as a relationship to {$generator->getBaseName()}");
        } elseif (!($targetType instanceof ObjectType)) {
            throw new Exception("{$typeName} is not a type for a relationship to {$generator->getBaseName()}");
        }
        $targetField = null;
        foreach ($targetType->getFields() as $subField) {
            $subDir = Parser::getDirectives($subField->astNode->directives);
            if (array_key_exists('morphTo', $subDir) || array_key_exists('morphedByMany', $subDir)) {
                $targetField = $subField->name;
                break;
            }
        }
        if (!$targetField) {
            throw new Exception("{$targetType} does not have a '@morphTo' or '@morphToMany' field");
        }

        $generator->class->addMethod($field->name)
        ->setReturnType('\Illuminate\Database\Eloquent\Relations\MorphMany')
            ->setPublic()
            ->setBody("return \$this->{$name}($typeName::class, '$targetField');");

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
