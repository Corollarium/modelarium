<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class ModelGenerator extends BaseGenerator
{
    public function generate(): GeneratedCollection
    {
        return new GeneratedCollection(
            [ new GeneratedItem(
                GeneratedItem::TYPE_MODEL,
                $this->generateString(),
                $this->getGenerateFilename()
            )]
        );
    }

    public function generateString(): string
    {
        return $this->stubToString('model');
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('app/Models/'. $this->studlyName . '.php');
    }
}
