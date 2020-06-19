<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\Type;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class EventGenerator extends BaseGenerator
{
    /**
     * @var GeneratedCollection
     */
    protected $events;

    public function generate(): GeneratedCollection
    {
        $this->events = new GeneratedCollection();
        
        foreach ($this->type->getFields() as $field) {
            $directives = $field->astNode->directives;
            $this->processDirectives($field, $directives);
        }
        return $this->events;
    }

    protected function makeEventClass(string $name, string $type): GeneratedItem
    {
        return new GeneratedItem(
            $name,
            $this->stubToString('event', function ($stub) use ($name, $type) {
                $eventTokens = explode('\\', $name);
                $eventClassName = array_pop($eventTokens);
                $eventNamespace = implode('\\', $eventTokens);
                $stub = str_replace(
                    'DummyEventNamespace',
                    $eventNamespace,
                    $stub
                );
                
                $stub = str_replace(
                    'DummyEventClassName',
                    $eventClassName,
                    $stub
                );
                
                $stub = str_replace(
                    'DummyTypeClass',
                    $type,
                    $stub
                );
                return $stub;
            }),
            $this->getGenerateFilename($name)
        );
    }

    public function processDirectives(
        \GraphQL\Type\Definition\FieldDefinition $field,
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        $type = $field->type->name;
        
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'event':
                $dispatch = '';
                
                foreach ($directive->arguments as $arg) {
                    /**
                     * @var \GraphQL\Language\AST\ArgumentNode $arg
                     */

                    $value = $arg->value->value;

                    switch ($arg->name->value) {
                    case 'dispatch':
                        $dispatch = $value;
                    break;
                    }
                }
                $this->events->push($this->makeEventClass($dispatch, $type));
                break;
            default:
            }
        }
    }

    public function getGenerateFilename(string $name): string
    {
        return $this->getBasePath(str_replace('\\', '/', $name) . '.php');
    }
}
