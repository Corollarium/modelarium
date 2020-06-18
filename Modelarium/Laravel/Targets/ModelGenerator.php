<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class ModelGenerator extends BaseGenerator
{
    public function generateString(): string
    {
        return $this->stubToString('model');
    }

    protected function getGenerateFilename(): string
    {
        return $this->getBasePath('app/Models/'. $this->studlyName . '.php');
    }
}
