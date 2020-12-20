<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

use Illuminate\Support\Str;
use LightnCandy\LightnCandy;

use function Safe\file_put_contents;

function createDirective(string $name, array $processors)
{
    $parameters = [
        'name' => $name,
        'namespace' => 'Modelarium\\Laravel\\Lighthouse\\Directives',
        'studlyName' => Str::studly($name),
        'event' => in_array('event', $processors),
        'factory' => in_array('factory', $processors),
        'model' => in_array('model', $processors),
        'migration' => in_array('migration', $processors),
        'policy' => in_array('policy', $processors),
        'seed' => in_array('seed', $processors),
        'implements' => implode(
            ', ',
            array_map(
                function ($i) {
                    return Str::studly($i) . 'DirectiveInterface';
                },
                $processors
            )
        )
    ];
    $template = \Safe\file_get_contents(__DIR__ . '/directive.lighthouse.mustache');
    $phpStr = LightnCandy::compile(
        $template,
        [
            'flags' => LightnCandy::FLAG_ERROR_EXCEPTION
        ]
    );
    if (!$phpStr) {
        throw new Exception('Invalid template');
    }
    /** @var callable $renderer */
    $renderer = LightnCandy::prepare($phpStr);
    $filename = __DIR__ . '/../Modelarium/Laravel/Lighthouse/Directives/' . $parameters['studlyName'] . 'Directive.php';
    file_put_contents($filename, $renderer($parameters));
    echo "Wrote $filename\n";

    $template = \Safe\file_get_contents(__DIR__ . '/directive.mustache');
    $phpStr = LightnCandy::compile(
        $template,
        [
            'flags' => LightnCandy::FLAG_ERROR_EXCEPTION
        ]
    );
    if (!$phpStr) {
        throw new Exception('Invalid template');
    }
    /** @var callable $renderer */
    $renderer = LightnCandy::prepare($phpStr);
    $renderer($parameters);
    $filename = __DIR__ . '/../Modelarium/Laravel/Directives/' . $parameters['studlyName'] . 'Directive.php';
    file_put_contents($filename, $renderer($parameters));
    echo "Wrote $filename\n";
}

// Script example.php
$longopts  = array(
    "name:",
    "processors:"
);
$options = getopt('', $longopts);
createDirective($options['name'], explode(',', $options['processors']));
