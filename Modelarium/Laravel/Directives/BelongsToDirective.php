<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use GraphQL\Type\Definition\ObjectType;
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
use Modelarium\Parser;

class BelongsToDirective implements MigrationDirectiveInterface, ModelDirectiveInterface, SeedDirectiveInterface
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
        MigrationCodeFragment $codeFragment
    ): void {
        $lowerName = mb_strtolower($generator->getInflector()->singularize($field->name));
        $fieldName = $lowerName . '_id';

        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name;
        $tableName = MigrationGenerator::toTableName($typeName);

        $targetType = $generator->parser->getType($typeName);
        if (!$targetType) {
            throw new DirectiveException("Cannot get type {$typeName} as a relationship to {$generator->getBaseName()}");
        } elseif (!($targetType instanceof ObjectType)) {
            throw new DirectiveException("{$typeName} is not a type for a relationship to {$generator->getBaseName()}");
        }
        // we don't know what is the reverse relationship name at this point. so let's guess all possibilities
        try {
            $targetField = $targetType->getField($tableName);
        } catch (\GraphQL\Error\InvariantViolation $e) {
            try {
                $targetField = $targetType->getField($generator->getTableName());
            } catch (\GraphQL\Error\InvariantViolation $e) {
                // one to one
                $targetField = $targetType->getField($generator->getLowerName());
            }
        }

        $targetDirectives = $targetField->astNode->directives;
        foreach ($targetDirectives as $targetDirective) {
            switch ($targetDirective->name->value) {
                case 'hasOne':
                case 'hasMany':
                    $codeFragment->appendBase('->unsignedBigInteger("' . $fieldName . '")');
                break;
            }
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
        \GraphQL\Language\AST\DirectiveNode $directive
    ): string {
        $lowerName = mb_strtolower($generator->getInflector()->singularize($field->name));
        $lowerNamePlural = $generator->getInflector()->pluralize($lowerName);

        $sourceTypeName = $generator->getLowerName();
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;

        $targetClass = Str::studly($generator->getInflector()->singularize($field->name));
        $generateRandom = true; // TODO
        $relationship = RelationshipFactory::RELATIONSHIP_ONE_TO_MANY;
        $isInverse = true;
        $generator->class->addMethod($lowerName)
            ->setPublic()
            ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo')
            ->setBody("return \$this->belongsTo($targetClass::class);");

        return $generator->getRelationshipDatatypeName(
            $relationship,
            $isInverse,
            $sourceTypeName,
            $targetTypeName
        );
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
