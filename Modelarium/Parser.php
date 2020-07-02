<?php declare(strict_types=1);

namespace Modelarium;

use Formularium\Formularium;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\ObjectType;
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

    protected function __construct()
    {
        $this->scalars = [
            'String' => 'Modelarium\\Types\\Datatype_string',
            'Int' => 'Modelarium\\Types\\Datatype_integer',
            'Float' => 'Modelarium\\Types\\Datatype_float',
            'Boolean' => 'Modelarium\\Types\\Datatype_bool',
        ];
    }

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
    public static function fromString(string $data): self
    {
        $p = new self();
        $p->ast = \GraphQL\Language\Parser::parse($data);
        $p->processAst();
        $schemaBuilder = new \GraphQL\Utils\BuildSchema(
            $p->ast,
            [__CLASS__, 'extendDatatypes']
        );
        
        $p->schema = $schemaBuilder->buildSchema();
        $p->processSchema();
        return $p;
    }

    /**
     *
     * @param string[] $sources
     * @return self
     */
    public static function fromStrings(array $sources): self
    {
        $p = new self();
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query']),
            'mutation' => new ObjectType(['name' => 'Mutation']),
        ]);

        foreach ($sources as &$s) {
            $s = \Safe\preg_replace('/^type Mutation/m', 'extend type Mutation', $s);
            $s = \Safe\preg_replace('/^type Query/m', 'extend type Query', $s);
        }
        $extensionSource = implode("\n\n", $sources);
        $p->ast = \GraphQL\Language\Parser::parse($extensionSource);

        // TODO: extendDatatypes
        $p->schema = SchemaExtender::extend(
            $schema,
            $p->ast
        );
        // $schemaBuilder = new \GraphQL\Utils\BuildSchema(
        //     $p->ast,
        //     [__CLASS__, 'extendDatatypes']
        // );

        // $p->schema = $schemaBuilder->buildSchema();
        $p->processAst();
        return $p;
    }

    /**
     *
     * @param array $files
     * @return self
     * @throws \Safe\Exceptions\FilesystemException
     */
    public static function fromFiles(array $files): self
    {
        $sources = [
            Formularium::validatorGraphqlDirectives()
        ];
        foreach ($files as $f) {
            $data = \Safe\file_get_contents($f);
            $sources = array_merge($sources, static::processImports($data, dirname($f)));
            $sources[] = $data;
        }
        return static::fromStrings($sources);
    }

    /**
     * Returns a Parser from a file path
     *
     * @param string $path The file path
     * @return Parser
     * @throws \Safe\Exceptions\FilesystemException If file is not found or parsing fails.
     */
    public static function fromFile(string $path): self
    {
        $data = \Safe\file_get_contents($path);
        $imports = static::processImports($data, dirname($path));
        // TODO: recurse imports
        return self::fromString(implode("\n", $imports) . $data);
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

    /**
     * @param string $data
     * @return string[]
     */
    protected static function processImports(string $data, string $basedir): array
    {
        $matches = [];
        $imports = \Safe\preg_match_all('/^#import\s+\"([^"]+)\"$/m', $data, $matches, PREG_SET_ORDER, 0);
        if (!$imports) {
            return [];
        }
        return array_map(
            function ($i) use ($basedir) {
                if ($i[1] === 'formularium.graphql') {
                    return \Safe\file_get_contents(__DIR__ . '/Types/Graphql/scalars.graphql');
                }
                return \Safe\file_get_contents($basedir . '/' . $i[1]);
            },
            $matches
        );
    }

    public function getSchema(): Schema
    {
        return $this->schema;
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
}
