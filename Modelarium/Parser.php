<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Utils\AST;
use GraphQL\Type\Definition\Type;

class Parser
{
    /**
     * @var string
     */
    protected $schemaContent;

    /**
     * @var GraphQL\Language\AST\DocumentNode
     */
    protected $schemaDocument;

    /**
     * @var GraphQL\Utils\BuildSchema
     */
    protected $schemaBuilder;

    /**
     * @var \GraphQL\Type\Schema
     */
    protected $schema;

    protected function __construct(string $data)
    {
        $this->schemaContent = $data;
        $this->schemaDocument = \GraphQL\Language\Parser::parse($this->schemaContent);
        $this->schemaBuilder = new \GraphQL\Utils\BuildSchema(
            $this->schemaDocument,
            function ($typeConfig, $typeDefinitionNode) {
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
        );
        $this->schema = $this->schemaBuilder->buildSchema(
            
        );
    }

    /**
     * Returns a Parser from a file path
     *
     * @param string $path The file path
     * @return Parser
     * @throws Exception If file is not found or parsing fails.
     */
    public static function fromPath(string $path): self
    {
        $data = file_get_contents($path);
        if (!$data) {
            throw new \Exception('Invalid path');
        }
        return new self($data);
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
        return new self($data);
    }

    /**
     * Get the value of schema
     *
     * @return GraphQL\Type\Definition\Type
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function getType($name) : ?Type
    {
        return $this->schema->getType($name);
    }
}
