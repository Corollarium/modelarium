<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets\Interfaces;

use Modelarium\Laravel\Targets\ModelGenerator;

interface ModelDirectiveInterface
{
    public static function processModelTypeDirective(
        ModelGenerator $generator,
        \GraphQL\Language\AST\Node $directive
    ): void;
}
