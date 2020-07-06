<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

use Formularium\Formularium;
use Modelarium\Laravel\Processor as LaravelProcessor;

$datatypes = Formularium::getDatatypeNames();
$datatypeMD = <<<EOF
# Datatypes\n\n

Automatically generated documentation for default datatypes from Formularium and Modelarium.

EOF;

foreach ($datatypes as $name) {
    $datatypeMD .= <<<EOF

## $name 

TODO: description

EOF;
}

$filename = __DIR__ . '/../docs/datatypes.md';
\Safe\file_put_contents($filename, $datatypeMD);

$directiveMD = <<<EOF
# Directives\n\n

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
$filename = __DIR__ . '/../docs/directives.md';
\Safe\file_put_contents($filename, $directiveMD);
echo "Generated.\n";
