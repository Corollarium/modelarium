<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Modelarium\BaseGenerator;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Illuminate\Support\Str;

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
        // @phpstan-ignore-next-line
        $laravelVersion = app()->version();
        if (Str::startsWith($laravelVersion, '6.x') || Str::startsWith($laravelVersion, '7.x')) {
            return $this->templateStub('factory');
        }
        return $this->templateStub('factory8');
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('database/factories/'. $this->studlyName . 'Factory.php');
    }
}
