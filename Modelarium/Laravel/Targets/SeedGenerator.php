<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class SeedGenerator extends BaseGenerator
{
    public function generate()
    {
        $path = $this->getBasePath('database/seeds/'. $this->studlyName . 'Seeder.php');
        $this->stubFile($path, 'seed');
    }
}
