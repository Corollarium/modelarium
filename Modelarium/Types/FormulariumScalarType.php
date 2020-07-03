<?php declare(strict_types=1);

namespace Modelarium\Types;

use Formularium\Datatype;
use Formularium\DatatypeFactory;
use Formularium\Exception\ClassNotFoundException;
use Formularium\Field;
use Formularium\ValidatorFactory;
use Formularium\ValidatorMetadata;
use Modelarium\Exception\Exception;

abstract class FormulariumScalarType extends ScalarType
{
    /**
     * @var Datatype
     */
    protected $datatype = null;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $name = mb_strtolower(str_replace('Datatype_', '', $this->name));
        $this->datatype = DatatypeFactory::factory($name);
    }

    public function getDatatype(): Datatype
    {
        return $this->datatype;
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @param string $value
     * @return string
     */
    public function serialize($value)
    {
        // $field = new Field(); // TODO
        // return $this->datatype->format($value, $field);
        return '';
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     * @return mixed
     */
    public function parseValue($value)
    {
        return $this->datatype->validate($value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * E.g.
     * {
     *   user(email: "user@example.com")
     * }
     *
     * @param \GraphQL\Language\AST\Node $valueNode
     * @param array|null $variables
     * @return string
     * @throws \Throwable
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        return $this->parseValue($valueNode->value); /** @phpstan-ignore-line */
    }

    public function processDirectives(
        string $fieldName,
        \GraphQL\Language\AST\NodeList $directives
    ): Field {
        $validators = [];
        $renderable = [];
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

            $validator = null;
            try {
                $validator = ValidatorFactory::class($name);
            } catch (ClassNotFoundException $e) {
                continue;
            }

            /**
             * @var ValidatorMetadata $metadata
             */
            $metadata = $validator::getMetadata();
            $arguments = [];

            foreach ($directive->arguments as $arg) {
                /**
                 * @var \GraphQL\Language\AST\ArgumentNode $arg
                 */

                $argName = $arg->name->value;
                $argValue = $arg->value->value; /** @phpstan-ignore-line */

                $argValidator = $metadata->argument($argName);
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
            $this->datatype->getName(),
            $renderable,
            $validators,
        );
    }

    /**
     * Returns the suggested SQL type for this datatype, such as 'TEXT'.
     *
     * @param string $database The database
     * @return string
     */
    public function getSQLType(string $database = '', array $options = []): string
    {
        return $this->datatype->getSQLType($database, $options);
    }

    /**
     * Returns the suggested Laravel Database type for this datatype.
     *
     * @return string
     */
    public function getLaravelSQLType(string $name, array $options = []): string
    {
        return $this->datatype->getLaravelSQLType($name, $options);
    }
}
