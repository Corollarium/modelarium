<?php declare(strict_types=1);

namespace Modelarium\Laravel\Directives;

use Modelarium\Laravel\Targets\SeedGenerator;
use Modelarium\Laravel\Targets\Interfaces\SeedDirectiveInterface;

class MorphedByManyDirective implements SeedDirectiveInterface
{
    public static function processSeedFieldDirective(
        SeedGenerator $generator,
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\DirectiveNode $directive
    ): void {
        // TODO $relation = Parser::getDirectiveArgumentByName($directive, 'relation', $lowerName);
    }
}
