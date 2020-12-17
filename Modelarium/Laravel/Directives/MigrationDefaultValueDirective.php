<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Laravel\Targets\MigrationGenerator;
use Modelarium\Laravel\Targets\Interfaces\MigrationDirectiveInterface;
use Modelarium\Laravel\Targets\MigrationCodeFragment;
use Modelarium\Parser;

class MigrationDefaultValueDirective implements MigrationDirectiveInterface
{
    public static function processMigrationTypeDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // nothing
    }

    public static function processMigrationFieldDirective(
        MigrationGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive,
        MigrationCodeFragment $code
    ): void {
        $x = Parser::getDirectiveArgumentByName($directive, 'value');
        $code->appendBase('->default(' . $x . ')');
    }
}
