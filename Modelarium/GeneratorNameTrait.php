<?php declare(strict_types=1);

namespace Modelarium;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Str;
use LightnCandy\LightnCandy;
use Modelarium\Exception\Exception;

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
     *
     * @param string $path
     * @return callable
     */
    protected function compileMustacheFromFile(string $path)
    {
        $template = \Safe\file_get_contents($path);
        $phpStr = LightnCandy::compile(
            $template,
            [
                'flags' => LightnCandy::FLAG_ERROR_EXCEPTION,
                'delimiters' => array('{|', '|}')
            ]
        );
        if (!$phpStr) {
            throw new Exception('Invalid template');
        }
        /** @var callable $renderer */
        $renderer = LightnCandy::prepare($phpStr);
        return $renderer;
    }

    /**
     * Replaces common strings from the stubs
     *
     * @param string $str The string data to apply replaces
     * @param array $context
     * @return string
     */
    public function templateFile(string $path, array $context = [])
    {
        $renderer = $this->compileMustacheFromFile($path);
        $context['StudlyName'] = $context['studlyName'] = $this->studlyName;
        $context['lowerName'] = $this->lowerName;
        $context['lowerNamePlural'] = $this->lowerNamePlural;
        $context['date'] = date("c");

        return $renderer($context);
    }

    /**
     * Stubs from a mustache file. Convenience wrapper for templateFile().
     *
     * @param string $stubName
     * @param array $context
     * @return string
     */
    public function templateStub(string $stubName, array $context = []): string
    {
        $stub = $this->stubDir . "/$stubName.mustache.php";
        return $this->templateFile($stub, $context);
    }

    /**
     * Replaces common strings from the stubs
     *
     * @param string $str The string data to apply replaces
     * @param string[] $replace
     * @return string
     * @deprecated
     */
    protected function template(string $str, array $replace = [])
    {
        $date = date("c");
        return strtr(
            $str,
            array_merge(
                [
                    '{{StudlyName}}' => $this->studlyName,
                    '{{ StudlyName }}' => $this->studlyName,
                    '{{lowerName}}' => $this->lowerName,
                    '{{ lowerName }}' => $this->lowerName,
                    '{{lowerNamePlural}}' => $this->lowerNamePlural,
                    '{{ lowerNamePlural }}' => $this->lowerNamePlural,
                    '{{date}}' => $date,
                    '{{ date }}' => $date
                ],
                $replace
            )
        );
    }
}
