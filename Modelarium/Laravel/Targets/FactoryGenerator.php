<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use GraphQL\Type\Definition\ObjectType;
use Modelarium\BaseGenerator;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Laravel\Util as LaravelUtil;

class FactoryGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

    public function generate(): GeneratedCollection
    {
        if (!($this->type instanceof ObjectType)) {
            throw new Exception('Invalid type on seed generator:' . get_class($this->type));
        }

        /**
         * @var \GraphQL\Language\AST\NodeList|null
         */
        $directives = $this->type->astNode->directives;
        if ($directives) {
            $this->processTypeDirectives($directives, 'Factory');
        }

        /**
         * @var ObjectType $t
         */
        $t = $this->type;
        foreach ($t->getFields() as $field) {
            $directives = $field->astNode->directives;
            $this->processFieldDirectives($field, $directives, 'Factory');
        }
        
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
        $laravelVersion = LaravelUtil::getLaravelVersion();
        if (Str::startsWith($laravelVersion, '6.') || Str::startsWith($laravelVersion, '7.')) {
            return $this->templateStub('factory');
        }
        return $this->templateStub('factory8');
    }

    public function getGenerateFilename(): string
    {
        return $this->getBasePath('database/factories/'. $this->studlyName . 'Factory.php');
    }
}
