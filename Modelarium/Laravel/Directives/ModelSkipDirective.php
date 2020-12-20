<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\SkipGenerationException;
use Modelarium\Laravel\Targets\EventGenerator;
use Modelarium\Laravel\Targets\FactoryGenerator;
use Modelarium\Laravel\Targets\Interfaces\EventDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\FactoryDirectiveInterface;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\PolicyDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;
use Modelarium\Laravel\Targets\SeedGenerator;

class ModelSkipDirective implements ModelDirectiveInterface, SeedDirectiveInterface, FactoryDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        throw new SkipGenerationException();
    }

    public static function processModelFieldDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \Formularium\Field $fieldFormularium,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processModelRelationshipDirective(
        ModelGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        \Formularium\Datatype $datatype = null
    ): ?\Formularium\Datatype {
        return null;
    }

    public static function processSeedTypeDirective(
        SeedGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        throw new SkipGenerationException();
    }

    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }

    public static function processFactoryTypeDirective(
        FactoryGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        throw new SkipGenerationException();
    }

    public static function processFactoryFieldDirective(
        FactoryGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
    }
}
