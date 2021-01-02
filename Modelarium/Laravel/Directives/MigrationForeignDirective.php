<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Exception\DirectiveException;
use Modelarium\Exception\Exception;
use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Parser;

class MigrationForeignDirective implements MigrationDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
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
        $tableName = MigrationGenerator::toTableName($field->name);
        $lowerName = mb_strtolower($generator->getInflector()->singularize($field->name));
        $fieldName = $lowerName . '_id';
        $directives = $field->astNode->directives;

        foreach ($directives as $directive) {
            $name = $directive->name->value;

            switch ($name) {
                case 'belongToMany':
                case 'morphedByMany':
                    throw new DirectiveException("Foreign keys cannot be used with many-to-many-relationships. Check field: " . $field->name);
                    break;
            }
        }

        $arguments = array_merge(
            [
                'references' => 'id',
                'on' => $tableName
            ],
            Parser::getDirectiveArguments($directive)
        );

        $code->appendExtraLine(
            '$table->foreign("' . $fieldName . '")' .
                "->references(\"{$arguments['references']}\")" .
                "->on(\"{$arguments['on']}\")" .
                (($arguments['onDelete'] ?? '') ? "->onDelete(\"{$arguments['onDelete']}\")" : '') .
                (($arguments['onUpdate'] ?? '') ? "->onUpdate(\"{$arguments['onUpdate']}\")" : '') .
                ';'
        );
    }
}
