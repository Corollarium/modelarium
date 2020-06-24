<?php declare(strict_types=1);

namespace Modelarium;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Utils\AST;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;

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

    protected function __construct()
    {
        // empty
    }

    /**
     *
     * @param array $files
     * @return self
     * @throws \Safe\Exceptions\FilesystemException
     */
    public static function fromFiles(array $files): self
    {
        $p = new self();
        $sources = array_map('\Safe\file_get_contents', $files);
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
        return self::fromString($data);
    }

    /**
     * Returns a Parser from a string
     *
     * @param string $data the string
     * @return Parser
     * @throws Exception If parsing fails.
     */
    public static function fromString(string $data): self
    {
        $p = new self();
        $p->ast = \GraphQL\Language\Parser::parse($data);
        $schemaBuilder = new \GraphQL\Utils\BuildSchema(
            $p->ast,
            [__CLASS__, 'extendDatatypes']
        );
        $p->schema = $schemaBuilder->buildSchema();
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
        $extensionSource = implode("\n", $sources);
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
        return $p;
    }


    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getType(string $name) : ?Type
    {
        return $this->schema->getType($name);
    }
}
