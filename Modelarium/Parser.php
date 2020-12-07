<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;
use Modelarium\Exception\ScalarNotFoundException;
use Modelarium\Types\ScalarType;

class Parser
{
    /**
     * @var \GraphQL\Language\AST\DocumentNode
     */
    protected $ast;

    /**
     * @var \GraphQL\Type\Schema
     */
    protected $schema;

    /**
     * @var string[]
     */
    protected $scalars = [];

    /**
     * @var string[]
     */
    protected $imports = [];

    public function __construct()
    {
        $this->scalars = [
            'String' => 'Modelarium\\Types\\Datatype_string',
            'Int' => 'Modelarium\\Types\\Datatype_integer',
            'Float' => 'Modelarium\\Types\\Datatype_float',
            'Boolean' => 'Modelarium\\Types\\Datatype_bool',
        ];

        $this->imports = [
            'formularium.graphql' => \Safe\file_get_contents(__DIR__ . '/Types/Graphql/scalars.graphql'),
        ];
    }

    /** @phpstan-ignore-next-line */
    public static function extendDatatypes(array $typeConfig, $typeDefinitionNode): array
    {
        /* TODO: extended datatypes
        if ($typeConfig['name'] === 'Email') {
            $typeConfig = array_merge($typeConfig, [
                'serialize' => function ($value) {
                    // ...
                },
                'parseValue' => function ($value) {
                    // ...
                },
                'parseLiteral' => function ($ast) {
                    // ...
                }
            ]);
        } */
        return $typeConfig;
    }

    /**
     * Returns a Parser from a string
     *
     * @param string $data the string
     * @return Parser
     */
    public function fromString(string $data): self
    {
        $this->ast = \GraphQL\Language\Parser::parse($data);
        $this->processAst();
        $schemaBuilder = new \GraphQL\Utils\BuildSchema(
            $this->ast,
            [__CLASS__, 'extendDatatypes']
        );

        $this->schema = $schemaBuilder->buildSchema();
        $this->processSchema();
        return $this;
    }

    /**
     *
     * @param string[] $sources
     * @return self
     */
    public function fromStrings(array $sources): self
    {
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query']),
            'mutation' => new ObjectType(['name' => 'Mutation']),
        ]);

        foreach ($sources as &$s) {
            $s = \Safe\preg_replace('/^type Mutation/m', 'extend type Mutation', $s);
            $s = \Safe\preg_replace('/^type Query/m', 'extend type Query', $s);
        }
        $extensionSource = implode("\n\n", $sources);
        try {
            $this->ast = \GraphQL\Language\Parser::parse($extensionSource);
        } catch (SyntaxError $e) {
            $source = $e->getSource();
            $start = $e->getPositions()[0] - 50;
            $end = $e->getPositions()[0] + 50;
            $start = $start <= 0 ? 0 : $start;
            $end = $end >= $source->length ? $source->length : $end;
            echo $e->message, "\nat: ...", mb_substr($source->body, $start, $end - $start), '...';
            throw $e;
        }

        // TODO: extendDatatypes
        $this->schema = SchemaExtender::extend(
            $schema,
            $this->ast
        );
        // $schemaBuilder = new \GraphQL\Utils\BuildSchema(
        //     $this->ast,
        //     [__CLASS__, 'extendDatatypes']
        // );

        // $this->schema = $schemaBuilder->buildSchema();
        $this->processAst();
        return $this;
    }

    /**
     *
     * @param array $files
     * @return self
     * @throws \Safe\Exceptions\FilesystemException
     */
    public function fromFiles(array $files): self
    {
        $sources = [
        ];
        foreach ($files as $f) {
            $data = \Safe\file_get_contents($f);
            $sources = array_merge($sources, $this->processImports($data, dirname($f)));
            $sources[] = $data;
        }
        return $this->fromStrings($sources);
    }

    /**
     * Returns a Parser from a file path
     *
     * @param string $path The file path
     * @return Parser
     * @throws \Safe\Exceptions\FilesystemException If file is not found or parsing fails.
     */
    public function fromFile(string $path): self
    {
        $data = \Safe\file_get_contents($path);
        $imports = $this->processImports($data, dirname($path));
        // TODO: recurse imports
        return $this->fromString(implode("\n", $imports) . $data);
    }

    protected function processSchema(): void
    {
        $originalTypeLoader = $this->schema->getConfig()->typeLoader;

        $this->schema->getConfig()->typeLoader = function ($typeName) use ($originalTypeLoader) {
            $type = $originalTypeLoader($typeName);
            if ($type instanceof \GraphQL\Type\Definition\CustomScalarType) {
                $scalarName = $type->name;
                $className = $this->scalars[$scalarName];
                return new $className($type->config);
            }
            return $type;
        };
    }

    protected function processAst(): void
    {
        $this->ast = Visitor::visit($this->ast, [
            // load the scalar type classes
            NodeKind::SCALAR_TYPE_DEFINITION => function ($node) {
                $scalarName = $node->name->value;

                // load classes
                $className = null;
                foreach ($node->directives as $directive) {
                    switch ($directive->name->value) {
                    case 'scalar':
                        foreach ($directive->arguments as $arg) {
                            /**
                             * @var \GraphQL\Language\AST\ArgumentNode $arg
                             */

                            $value = $arg->value->value;

                            switch ($arg->name->value) {
                            case 'class':
                                $className = $value;
                            break;
                            }
                        }
                    break;
                    }
                }

                // Require special handler class for custom scalars:
                if (!class_exists($className, true)) {
                    throw new \Modelarium\Exception\Exception(
                        "Custom scalar must have corresponding handler class $className"
                    );
                }

                $this->scalars[$scalarName] = $className;

                // return
                //   null: no action
                //   Visitor::skipNode(): skip visiting this node
                //   Visitor::stop(): stop visiting altogether
                //   Visitor::removeNode(): delete this node
                //   any value: replace this node with the returned value
                return null;
            }
        ]);
    }

    public function setImport(string $name, string $data): self
    {
        $this->imports[$name] = $data;
        return $this;
    }

    /**
     * @param string $data
     * @return string[]
     */
    protected function processImports(string $data, string $basedir): array
    {
        $matches = [];
        $imports = \Safe\preg_match_all('/^#import\s+(.+)$/m', $data, $matches, PREG_SET_ORDER, 0);
        if (!$imports) {
            return [];
        }
        return array_map(
            function ($i) use ($basedir) {
                $name = $i[1];
                if (array_key_exists($name, $this->imports)) {
                    return $this->imports[$name];
                }
                return \Safe\file_get_contents($basedir . '/' . $name);
            },
            $matches
        );
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getAST(): DocumentNode
    {
        return $this->ast;
    }

    public function getType(string $name) : ?Type
    {
        return $this->schema->getType($name);
    }

    public function getScalars(): array
    {
        return $this->scalars;
    }

    /**
     * Factory.
     *
     * @param string $datatype
     * @return ScalarType
     * @throws ScalarNotFoundException
     */
    public function getScalarType(string $datatype): ?ScalarType
    {
        $className = $this->scalars[$datatype] ?? null;
        if (!$className) {
            return null;
        }
        if (!class_exists($className)) {
            throw new ScalarNotFoundException("Class not found for $datatype ($className)");
        }
        return new $className();
    }

    /**
     * Given a list of directives, return an array with [ name => [ argument => value] ]
     *
     * @param NodeList $list
     * @return array
     */
    public static function getDirectives(NodeList $list): array
    {
        $directives = [];
        foreach ($list as $d) {
            /**
             * @var DirectiveNode $d
             */
            $directives[$d->name->value] = self::getDirectiveArguments($d);
        }
        return $directives;
    }

    /**
     * Gets unwrapped type
     *
     * @param OutputType $type
     * @return array [OutputType type, bool isRequired]
     */
    public static function getUnwrappedType(OutputType $type): array
    {
        $ret = $type;
        $isRequired = false;

        if ($ret instanceof NonNull) {
            $ret = $ret->getWrappedType();
            $isRequired = true;
        }

        if ($ret instanceof ListOfType) {
            $ret = $ret->getWrappedType();
            if ($ret instanceof NonNull) { /** @phpstan-ignore-line */
                $ret = $ret->getWrappedType();
            }
        }

        return [$ret, $isRequired];
    }

    /**
     * Convertes a directive node arguments to an associative array.
     *
     * @param DirectiveNode $directive
     * @return array
     */
    public static function getDirectiveArguments(DirectiveNode $directive): array
    {
        $data = [];
        foreach ($directive->arguments as $arg) {
            /**
             * @var ArgumentNode $arg
             */
            $data[$arg->name->value] = $arg->value->value; /** @phpstan-ignore-line */
        }
        return $data;
    }

    /**
     * Gets a directive argument value given its name
     *
     * @param DirectiveNode $directive
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getDirectiveArgumentByName(DirectiveNode $directive, string $name, $default = null)
    {
        foreach ($directive->arguments as $arg) {
            /**
             * @var ArgumentNode $arg
             */
            if ($arg->name->value === $name) {
                return $arg->value->value; /** @phpstan-ignore-line */
            }
        }
        return $default;
    }

    /**
     * Appends a scalar in realtime
     *
     * @param string $scalarName The scalar name
     * @param string $className The FQCN
     * @return self
     */
    public function appendScalar(string $scalarName, string $className): self
    {
        $this->scalars[$scalarName] = $className;
        return $this;
    }
}
