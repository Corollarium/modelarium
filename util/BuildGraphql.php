<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

use Formularium\Datatype;
use Formularium\Factory\DatatypeFactory;
use Formularium\Formularium;
use Modelarium\Laravel\Processor as LaravelProcessor;
use Modelarium\Modelarium;
use Modelarium\Util as ModelariumUtil;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\realpath;

$ns = "Modelarium\\Types";
$formulariumTypes = DatatypeFactory::getNames();
foreach ($formulariumTypes as $classname => $type) {
    $stub = ModelariumUtil::generateLighthouseTypeFile($type, $ns);
    $filename = __DIR__ . '/../Modelarium/Types/Datatype_' . $type . '.php';
    \Safe\file_put_contents($filename, $stub);
}

$graphql = ModelariumUtil::scalars($formulariumTypes, $ns);
$filename = realpath(__DIR__ . '/../Modelarium/Types/Graphql/scalars.graphql');
file_put_contents($filename, implode("\n\n", $graphql));
echo "Generated $filename.\n";

// TODO: this is currently tied to Laravel
$filename = realpath(__DIR__ . '/../Modelarium/Types/Graphql/directives.graphql');
file_put_contents(
    $filename,
    LaravelProcessor::getDirectivesGraphqlString() . "\n\n" .
    file_get_contents(__DIR__ . '/../Modelarium/Laravel/Graphql/auxiliaryTypes.graphql')
);
echo "Generated $filename.\n";
