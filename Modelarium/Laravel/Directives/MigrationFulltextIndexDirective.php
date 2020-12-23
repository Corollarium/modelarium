<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\DirectiveException;
use Modelarium\Exception\Exception;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\ModelGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\Interfaces\ModelDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Parser;

class MigrationFulltextIndexDirective implements MigrationDirectiveInterface, ModelDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $indexFields = Parser::getDirectiveArgumentByName($directive, 'fields');

        if (!count($indexFields)) {
            throw new Exception("You must provide at least one field to a full text index");
        }
        $generator->postCreateCode[] = "DB::statement('ALTER TABLE " .
            $generator->getTableName()  .
            " ADD FULLTEXT fulltext_index (\"" .
            implode('", "', $indexFields) .
            "\")');";
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
        throw new DirectiveException("Directive not supported here");
    }

    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        $indexFields = Parser::getDirectiveArgumentByName($directive, 'fields');
        $indexFieldsString = join(', ', $indexFields);
        
        $method = $generator->class->addMethod('scopeFulltext')
            ->setPublic()
            ->setComment('
Scope a query to use the fulltext index

@param  \Illuminate\Database\Eloquent\Builder  $query
@param  string $needle
@return \Illuminate\Database\Eloquent\Builder
           ')
            ->setReturnType('\\Illuminate\\Database\\Eloquent\\Builder')
            ->setBody("return \$query->whereRaw(\"MATCH ($indexFieldsString) AGAINST (? IN NATURAL LANGUAGE MODE)\", [\$needle]);");
        
        $method->addParameter('query')
            ->setType('\\Illuminate\\Database\\Eloquent\\Builder');
        $method->addParameter('needle')
            ->setType('string');
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
        return null;
    }
}
