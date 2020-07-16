<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Type\Definition\Type;
use Modelarium\Exception\Exception;
use Modelarium\Parser;

abstract class BaseGenerator implements GeneratorInterface
{
    use GeneratorNameTrait;

    /**
     * @var string
     */
    protected $stubDir = null;

    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @var Type
     */
    protected $type = null;

    /**
     * @param Parser $parser
     * @param string $name The target type name.
     * @param Type|string $type
     */
    public function __construct(Parser $parser, string $name, $type = null)
    {
        $this->parser = $parser;
        $this->setName($name);

        if ($type instanceof Type) {
            $this->type = $type;
        } elseif (!$type) {
            $this->type = $parser->getSchema()->getType($name);
        } else {
            throw new Exception('Invalid model');
        }
    }
}
