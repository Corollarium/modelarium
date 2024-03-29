<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Formularium\Factory\DatatypeFactory;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Parser;
use Modelarium\Datatype\RelationshipFactory;
use Modelarium\Exception\DirectiveException;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Laravel\Targets\MigrationGenerator;

class MorphedByManyDirective implements MigrationDirectiveInterface, ModelDirectiveInterface, SeedDirectiveInterface
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
        $lowerName = $generator->getInflector()->singularize($field->name);
        $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
        $generator->generateManyToManyMorphTable($generator->getLowerName(), $relation);
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
        $name = $directive->name->value;
        list($type, $isRequired) = Parser::getUnwrappedType($field->getType());
        $typeName = $type->name;

        $lowerName = lcfirst($generator->getInflector()->singularize($field->name));
        $lowerNamePlural = $generator->getInflector()->pluralize($lowerName);

        $sourceTypeName = $generator->getBaseName();
        $targetTypeName = $lowerName;
        $relationship = null;
        $isInverse = false;
        $targetClass = '\\App\\Models\\' . Str::studly($generator->getInflector()->singularize($field->name));
        $generateRandom = true; // TODO

        $relationship = RelationshipFactory::MORPH_MANY_TO_MANY; // TODO
        $isInverse = true;
        $typeMap = $generator->parser->getSchema()->getTypeMap();

        foreach ($typeMap as $name => $object) {
            if (!($object instanceof ObjectType) || $name === 'Query' || $name === 'Mutation' || $name === 'Subscription') {
                continue;
            }

            /**
             * @var ObjectType $object
             */

            if (str_starts_with((string)$name, '__')) {
                // internal type
                continue;
            }

            foreach ($object->getFields() as $subField) {
                $subDirectives = Parser::getDirectives($subField->astNode->directives);

                if (!array_key_exists('morphToMany', $subDirectives)) {
                    continue;
                }

                $methodName = lcfirst($generator->getInflector()->pluralize((string)$name));
                $generator->class->addMethod($methodName)
                        ->setReturnType('\\Illuminate\\Database\\Eloquent\\Relations\\MorphToMany')
                        ->setPublic()
                        ->setBody("return \$this->morphedByMany($name::class, '$lowerName');");
            }
        }

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
