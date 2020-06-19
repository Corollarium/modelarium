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
            $this->processDirectives($directives);
        }
        return $this->events;
    }

    protected function makeEventClass(string $name): GeneratedItem
    {
        return new GeneratedItem(
            $name,
            $this->stubToString('migration', function ($stub) use ($name) {
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
                return $stub;
            }),
            $this->getGenerateFilename($name)
        );
    }

    public function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): void {
        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'event':
                $dispatch = '';
                $this->events->push($this->makeEventClass($dispatch));
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
