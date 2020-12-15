<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Parser;
use Modelarium\Datatypes\RelationshipFactory;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;

class MorphOneDirective implements ModelDirectiveInterface, SeedDirectiveInterface
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
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        $name = $directive->name->value;
        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name;

        $lowerName = mb_strtolower($generator->getInflector()->singularize($field->name));
        $lowerNamePlural = $generator->getInflector()->pluralize($lowerName);

        $sourceTypeName = $generator->getLowerName();
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;
        $targetClass = '\\App\\Models\\' . Str::studly($generator->getInflector()->singularize($field->name));
        $generateRandom = true; // TODO

        $relationship = RelationshipFactory::MORPH_ONE_TO_ONE;
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
            ->setReturnType('\Illuminate\Database\Eloquent\Relations\MorphOne')
            ->setPublic()
            ->setBody("return \$this->{$name}($typeName::class, '$targetField');");

        return $generator->getRelationshipDatatypeName(
            $relationship,
            $isInverse,
            $sourceTypeName,
            $targetTypeName
        );
    }

    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $type1 = $generator->getLowerName();
        $type2 = mb_strtolower($generator->getInflector()->singularize($field->name));

        if (strcasecmp($type1, $type2) < 0) { // TODO: check this, might not work
            $relationship = mb_strtolower($generator->getInflector()->pluralize($field->name));
            $generator->extraCode[] = self::makeManyToManySeed($type1, $type2, $relationship);
        }
    }

    protected static function makeManyToManySeed(string $sourceModel, string $targetModel, string $relationship): string
    {
        return <<<EOF

        try {
            \${$targetModel}Items = App\\Models\\$targetModel::all();
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
