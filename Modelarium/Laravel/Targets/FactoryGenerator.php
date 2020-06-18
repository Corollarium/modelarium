<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class FactoryGenerator extends BaseGenerator
{
    public function generateString(): string
    {
        return $this->stubToString('factory');
    }

    protected function getGenerateFilename(): string
    {
        return $this->getBasePath('database/seeds/'. $this->studlyName . 'Factory.php');
    }
}
