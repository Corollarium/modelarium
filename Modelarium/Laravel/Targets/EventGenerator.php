<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class EventGenerator extends BaseGenerator
{
    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_EVENT,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
    }

    protected $eventClass = null;

    public function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $db = [];

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'event':
                var_dump($directive->arguments);
                $this->eventClass = $directive->arguments[0]->value->value;
                break;
            default:
            }
        }
        
        return $db;
    }

    public function generateString(): string
    {
        return $this->stubToString('event', function ($stub) {
            /**
             * @var Type
             */
            $modelData = $this->model->getSchema()->getType('Mutation');
            assert($modelData !== null);

            foreach ($modelData->getFields() as $field) {
                $this->processDirectives($modelData->astNode->directives);

                assert($this->eventClass !== null);
                $eventTokens = explode('\\', $this->eventClass);
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
            }

            return $stub;
        });
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath(str_replace('\\', '/', $this->eventClass) . '.php');
    }
}
