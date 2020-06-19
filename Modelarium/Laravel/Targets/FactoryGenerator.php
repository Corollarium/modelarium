<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class FactoryGenerator extends BaseGenerator
{
    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_FACTORY,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
    }

    public function generateString(): string
    {
        return $this->stubToString('factory');
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('database/seeds/'. $this->studlyName . 'Factory.php');
    }
}
