<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class FactoryGenerator extends BaseGenerator
{
    public function generate()
    {
        $path = $this->getBasePath('database/seeds/'. $this->studlyName . 'Factory.php');
        $this->stubFile($path, 'factory');
    }
}
