<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

use Formularium\Factory\DatatypeFactory;
use Formularium\Formularium;
use Modelarium\Laravel\Processor as LaravelProcessor;

function datatypes()
{
    $markdown = DatatypeFactory::map(
        function (\ReflectionClass $reflection): array {
            $class = $reflection->getName();
    
            /**
             * @var Datatype $d
             */
            $d = new $class(); // TODO: factory would be better
            return [
                'name' => $class,
                'value' => $d->getMetadata()->toMarkdown()
            ];
        }
    );

    ksort($markdown);

    $datatypeAPI = '
---
nav_order: 12
---

# Datatype reference

List of datatypes and its parameters generated automatically.

' . join("\n", $markdown);

    file_put_contents(__DIR__ . '/../docs/api-datatypes.md', $datatypeAPI);
}

function directives()
{
    $directiveMD = <<<EOF
---
nav_order: 12
---
        
# Directive reference

Directives supported by Modelarium.

EOF;
    $directives = LaravelProcessor::getDirectivesGraphql();
    foreach ($directives as $name => $directive) {
        $directiveMD .= <<<EOF

## @$name

```graphql
$directive
```

EOF;
    }
    $filename = __DIR__ . '/../docs/api-directives.md';
    \Safe\file_put_contents($filename, $directiveMD);
}

datatypes();
directives();
echo "Generated.\n";
