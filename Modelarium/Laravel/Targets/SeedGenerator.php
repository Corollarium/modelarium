<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\ObjectType;
use Modelarium\BaseGenerator;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Parser;

class SeedGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

    /**
     * Extra seed code generated by parser
     *
     * @var string[]
     */
    public $extraCode = [];

    public function generate(): GeneratedCollection
    {
        if (!($this->type instanceof ObjectType)) {
            throw new Exception('Invalid type on seed generator:' . get_class($this->type));
        }
        /**
         * @var ObjectType $t
         */
        $t = $this->type;
        foreach ($t->getFields() as $field) {
            $directives = $field->astNode->directives;
            $this->processDirectives($field, $directives);
        }

        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_SEED,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
    }

    public function processDirectives(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $className = $this->getDirectiveClass($name);
            if ($className) {
                call_user_func(
                    [$className, 'processSeedFieldDirective'],
                    [$this, $field, $directive]
                );
            }
        }
    }

    public function generateString(): string
    {
        return $this->templateStub(
            'seed',
            [
                'extraCode' => join("\n", $this->extraCode)
            ]
        );
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('database/seeds/'. $this->studlyName . 'Seeder.php');
    }
}
