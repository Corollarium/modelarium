<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\BaseGenerator;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

class FactoryGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

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
        return $this->getBasePath('database/factories/'. $this->studlyName . 'Factory.php');
    }
}
