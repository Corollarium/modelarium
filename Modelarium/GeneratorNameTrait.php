<?php declare(strict_types=1);

namespace Modelarium;

use Doctrine\Inflector\InflectorFactory;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\Parser;

trait GeneratorNameTrait
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $studlyName = '';

    /**
     * @var string
     */
    protected $lowerName = '';

    /**
     * @var string
     */
    protected $lowerNamePlural = '';

    public function getInflector(): \Doctrine\Inflector\Inflector
    {
        static $inflector = null;
        if (!$inflector) {
            $inflector = InflectorFactory::create()->build();
        }
        return $inflector;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
        $this->studlyName = Str::studly($this->name);
        $this->lowerName = mb_strtolower($this->name);
        $this->lowerNamePlural = $this->getInflector()->pluralize($this->lowerName);
    }

    /**
     * Splits a fully qualified class name into its namespace, class name and relative path
     *
     * @param string $fullclass
     * @return array
     */
    protected static function splitClassName(string $fullclass): array
    {
        $classTokens = explode('\\', $fullclass);
        $className = array_pop($classTokens);
        $classNamespace = implode('\\', $classTokens);
        $relativePath = implode('/', $classTokens);
        return [$classNamespace, $className, $relativePath];
    }

    /**
     * Returns the base path (where composer.json is located)
     *
     * @param string $file The filename
     * @return string
     */
    public static function getBasePath(string $file = null): string
    {
        $basepath = dirname(\Composer\Factory::getComposerFile());
        if ($file) {
            $basepath .= '/' . $file;
        }
        return $basepath;
    }

    /**
     * Get the value of name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of studlyName
     *
     * @return  string
     */
    public function getStudlyName()
    {
        return $this->studlyName;
    }

    /**
     * Replaces common strings from the stubs
     *
     * @param string $str The string data to apply replaces
     * @return string
     */
    protected function template(string $str)
    {
        $date = date("c");
        return str_replace(
            [
                '{{StudlyName}}',
                '{{ StudlyName }}',
                '{{lowerName}}',
                '{{ lowerName }}',
                '{{lowerNamePlural}}',
                '{{ lowerNamePlural }}',
                '{{date}}',
                '{{ date }}',
            ],
            [
                $this->studlyName,
                $this->studlyName,
                $this->lowerName,
                $this->lowerName,
                $this->lowerNamePlural,
                $this->lowerNamePlural,
                $date,
                $date
            ],
            $str
        );
    }
}