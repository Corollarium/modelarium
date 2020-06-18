<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class PolicyGenerator extends BaseGenerator
{
    public function processDirectives(
        \GraphQL\Language\AST\NodeList $directives
    ): array {
        $db = [];

        foreach ($directives as $directive) {
            $name = $directive->name->value;
            switch ($name) {
            case 'can':
                // ability
                // model
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
            $modelData = $this->model->getSchema()->getType($this->targetName);
            assert($modelData !== null);

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
            return $stub;
        });
    }

    protected function getGenerateFilename(): string
    {
        return $this->getBasePath('app/Policies/'. $this->studlyName . 'Policy.php');
    }
}