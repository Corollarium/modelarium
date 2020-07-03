<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

use Formularium\Datatype;
use Formularium\Formularium;
use Illuminate\Support\Str;
use Modelarium\Laravel\Processor as LaravelProcessor;

$graphql = [
    '"""
This file is auto generated.
"""'
];
$datatypes = Formularium::getDatatypeNames();
foreach ($datatypes as $name) {
    $typeName = Str::studly($name);

    if ($typeName === 'String' ||
        $typeName === 'Boolean' ||
        $typeName === 'Int' ||
        $typeName === 'Float'
    ) {
        // base types
        continue;
    }

    $stub = <<<EOF
<?php declare(strict_types=1);
/**
 * This file is automatically generated by Formularium.
 */
namespace Modelarium\Types;

class Datatype_$name extends FormulariumScalarType
{
}
EOF;

    $filename = __DIR__ . '/../Modelarium/Types/Datatype_' . $name . '.php';
    \Safe\file_put_contents($filename, $stub);

    $graphql[] = "scalar $typeName @scalar(class: \"" .
        str_replace('\\', '\\\\', 'Modelarium\\Types\\Datatype_' . $name) .
        "\")";
}

$graphqlData = implode("\n\n", $graphql);
$filename = __DIR__ . '/../Modelarium/Types/Graphql/scalars.graphql';
\Safe\file_put_contents($filename, $graphqlData);

\Safe\file_put_contents(
    __DIR__ . '/../Modelarium/Types/Graphql/directives.graphql',
    LaravelProcessor::getDirectivesGraphqlString()
);
echo "Generated.\n";
