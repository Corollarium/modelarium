<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Exception\ClassNotFoundException;
use Formularium\Extradata;
use Formularium\ExtradataParameter;
use Formularium\Field;
use Formularium\Factory\ValidatorFactory;
use Formularium\Metadata;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use Modelarium\Exception\Exception;

class FormulariumUtils
{
    public static function getFieldFromDirectives(
        string $fieldName,
        string $datatypeName,
        NodeList $directives
    ): Field {
        $validators = [];
        $renderable = [];
        $extradata = [];
        foreach ($directives as $directive) {
            $name = $directive->name->value;

            if ($name === 'renderable') {
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $argName = $arg->name->value;
                    $argValue = $arg->value->value; /** @phpstan-ignore-line */
                    $renderable[$argName] = $argValue;
                }
                continue;
            }

            $extradata[] = FormulariumUtils::directiveToExtradata($directive);

            $validator = null;
            try {
                $validator = ValidatorFactory::class($name);
            } catch (ClassNotFoundException $e) {
                continue;
            }

            /**
             * @var Metadata $metadata
             */
            $metadata = $validator::getMetadata();
            $arguments = [];

            foreach ($directive->arguments as $arg) {
                /**
                 * @var \GraphQL\Language\AST\ArgumentNode $arg
                 */

                $argName = $arg->name->value;
                $argValue = $arg->value->value; /** @phpstan-ignore-line */

                $argValidator = $metadata->parameter($argName);
                if (!$argValidator) {
                    throw new Exception("Directive $validator does not have argument $argName");
                }
                if ($argValidator->type === 'Int') {
                    $argValue = (int)$argValue;
                }
                $arguments[$argName] = $argValue;
            }

            $validators[$name] = $arguments;
        }

        return new Field(
            $fieldName,
            $datatypeName,
            $renderable,
            $validators,
            $extradata
        );
    }

    public static function directiveToExtradata(DirectiveNode $directive): Extradata
    {
        $metadataArgs = [];
        foreach ($directive->arguments as $arg) {
            $metadataArgs[] = new ExtradataParameter(
                $arg->name->value,
                // @phpstan-ignore-next-line
                $arg->value->value
            );
        }
        return new Extradata(
            $directive->name->value,
            $metadataArgs
        );
    }
}
