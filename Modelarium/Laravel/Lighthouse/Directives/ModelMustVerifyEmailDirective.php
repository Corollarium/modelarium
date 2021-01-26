<?php declare(strict_types=1);

namespace Modelarium\Laravel\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

class ModelMustVerifyEmailDirective extends BaseDirective
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Use a MustVerifyEmail trait on a model
"""
directive @modelMustVerifyEmail on OBJECT
SDL;
    }
}
