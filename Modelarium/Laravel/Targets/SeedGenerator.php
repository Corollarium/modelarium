<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class SeedGenerator extends BaseGenerator
{
    public function generateString(): string
    {
        return $this->stubToString('seed');
    }

    protected function getGenerateFilename(): string
    {
        return $this->getBasePath('database/seeds/'. $this->studlyName . 'Seeder.php');
    }
}
