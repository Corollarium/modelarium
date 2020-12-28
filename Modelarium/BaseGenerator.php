<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Type\Definition\Type;
use Illuminate\Console\Concerns\InteractsWithIO;
use Modelarium\Exception\Exception;
use Modelarium\Exception\SkipGenerationException;
use Modelarium\Parser;

use function Safe\class_implements;
use function Safe\date;

abstract class BaseGenerator implements GeneratorInterface
{
    use GeneratorNameTrait;
    use InteractsWithIO;

    /**
     * @var string
     */
    protected $stubDir = null;

    /**
     * @var Parser
     */
    public $parser = null;

    /**
     * @var Type
     */
    protected $type = null;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    public $outputSymfony;

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    public $outputStyle;

    /**
     * @param Parser $parser
     * @param string $name The target type name.
     * @param Type|string $type
     */
    public function __construct(Parser $parser, string $name, $type = null)
    {
        $this->parser = $parser;
        $this->setBaseName($name);

        if ($type instanceof Type) {
            $this->type = $type;
        } elseif (!$type) {
            $this->type = $parser->getSchema()->getType($name);
        } else {
            throw new Exception('Invalid model');
        }
        $this->input = new \Symfony\Component\Console\Input\StringInput("");
        $this->outputSymfony = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->outputStyle = new \Illuminate\Console\OutputStyle($this->input, $this->outputSymfony);
        $this->output = $this->outputStyle;
    }

    protected function phpHeader(): string
    {
        $date = date('c');
        return <<<EOF
<?php declare(strict_types=1);
/** 
 * This file was automatically generated by Modelarium.
 */

EOF;
    }

    /**
     * Gets the classname for a directive implementation interface class.
     *
     * @param string $directive The directive name.
     * @param string $type The type, such as 'Seeder' or 'Model'.
     * @return string|null
     */
    public function getDirectiveClass(
        string $directive,
        string $type = ''
    ): ?string {
        foreach (Modelarium::getGeneratorDirectiveNamespaces() as $ns) {
            $className = $ns . '\\' . ucfirst($directive) . 'Directive';
            if (!$type) {
                $parts = explode("\\", get_called_class());
                $type = end($parts);
                $type = str_replace('Generator', '', $type);
            }
            if (class_exists($className)
                && array_key_exists('Modelarium\\Laravel\\Targets\\Interfaces\\' . $type . 'DirectiveInterface', class_implements($className))
            ) {
                return $className;
            }
        }
        return null;
    }

    /**
     * Process all directives from list with directive classes.
     *
     * @param \GraphQL\Language\AST\NodeList $directives The directive list
     * @param string $generatorType The generatorType, like 'Seed' or 'Model'
     * @return void
     * @throws SkipGenerationException
     */
    protected function processTypeDirectives(
        \GraphQL\Language\AST\NodeList $directives,
        string $generatorType
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
    
            $className = $this->getDirectiveClass($name, $generatorType);
            if ($className) {
                $methodName = "$className::process{$generatorType}TypeDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $directive
                );
            }
        }
    }

    /**
     * Process all directives from list with directive classes.
     *
     * @param \GraphQL\Type\Definition\FieldDefinition $field
     * @param \GraphQL\Language\AST\NodeList $directives The directive list
     * @param string $generatorType The generatorType, like 'Seed' or 'Model'
     * @return void
     * @throws SkipGenerationException
     */
    public function processFieldDirectives(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives,
        string $generatorType
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            $className = $this->getDirectiveClass($name);
            if ($className) {
                $methodName = "$className::process{$generatorType}FieldDirective";
                /** @phpstan-ignore-next-line */
                $methodName(
                    $this,
                    $field,
                    $directive
                );
            }
        }
    }
}
