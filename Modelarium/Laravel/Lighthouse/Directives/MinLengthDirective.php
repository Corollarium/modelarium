<?php

namespace Modelarium\Laravel\Lighthouse\Directives;

use Closure;
use Formularium\DatatypeFactory;
use Formularium\Validator\MinLength;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Support\Contracts\ProvidesRules;
use Nuwave\Lighthouse\Support\Traits\HasResolverArguments;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\DefinedDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class MinLengthDirective extends BaseDirective implements FieldMiddleware, DefinedDirective
{
    use HasResolverArguments;

    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'SDL'
"""
Base class to extend on model.
"""
directive @minLength(
    """
    The base class name with namespace
    """
    value: Int!
) on OBJECT
SDL;
    }

    /**
     * Resolve the field directive.
     */
    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $previousResolver = $fieldValue->getResolver();

        // Wrap around the resolver
        $wrappedResolver = function ($root, array $args, GraphQLContext $context, ResolveInfo $info) use ($previousResolver): string {
            error_log("xxxxxx");

            // Call the resolver, passing along the resolver arguments
            /** @var string $result */
            $result = $previousResolver($root, $args, $context, $info);

            MinLength::validate(
                $result,
                ['value' => $this->directiveArgValue('value')]
            );
            return $result;
        };

        // Place the wrapped resolver back upon the FieldValue
        // It is not resolved right now - we just prepare it
        $fieldValue->setResolver($wrappedResolver);

        // Keep the middleware chain going
        return $next($fieldValue);
    }
}
