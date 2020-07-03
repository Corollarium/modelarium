<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Modelarium\Laravel\Targets\MigrationGenerator;

interface MigrationDirectiveInterface
{
    public static function processMigrationDirective(
        MigrationGenerator $generator,
        \GraphQL\Language\AST\Node $directive
    ): void;
}
