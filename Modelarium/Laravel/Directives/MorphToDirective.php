<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Faker\Provider\File;
use Formularium\ExtradataParameter;
use Formularium\Factory\DatatypeFactory;
use Formularium\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Parser;
use Modelarium\Datatypes\RelationshipFactory;
use Modelarium\Exception\DirectiveException;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Laravel\Targets\MigrationGenerator;

class MorphToDirective implements MigrationDirectiveInterface, ModelDirectiveInterface, SeedDirectiveInterface
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
        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
        $codeFragment->appendBase('->unsignedBigInteger("' . $relation . '_id")');
        $codeFragment->appendExtraLine(
            '$table->string("' . $relation . '_type")' .
            ($isRequired ? '' : '->nullable()') . ';'
        );
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
        list($type, $isRequired) = Parser::getUnwrappedType($field->type);
        $typeName = $type->name;

        if (!($type instanceof UnionType)) {
            throw new Exception("$typeName is declared as @morphTo target but it is not a union type.");
        }
        $unionTypes = $type->getTypes();
        $morphableTargets = [];
        foreach ($unionTypes as $t) {
            if (!($t instanceof ObjectType)) {
                throw new Exception("$typeName is declared in a @morphTo union but it's not an object type.");
            }

            /**
             * @var ObjectType $t
             */
            $morphableTargets[] = $t->name;
        }

        $fieldFormularium->getExtradata('morphTo')
            ->appendParameter(new ExtradataParameter('targetModels', implode(',', $morphableTargets)));
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        $lowerName = mb_strtolower($generator->getInflector()->singularize($field->name));

        $sourceTypeName = $generator->getLowerName();
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;
        $generateRandom = true; // TODO

        $relationship = RelationshipFactory::MORPH_ONE_TO_MANY; // TODO
        $isInverse = true;
        $generator->class->addMethod($field->name)
            ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\MorphTo')
            ->setPublic()
            ->setBody("return \$this->morphTo();");

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
        $type2 = mb_strtolower($generator->getInflector()->singularize($field->name));

        if (strcasecmp($type1, $type2) < 0) { // TODO: check this, might not work
            $relationship = mb_strtolower($generator->getInflector()->pluralize($field->name));
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
