<?php declare(strict_types=1);

namespace Modelarium;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Str;
use LightnCandy\LightnCandy;
use Modelarium\Exception\Exception;
use function Safe\date;

trait GeneratorNameTrait
{
    /**
     * @var string
     */
    protected $baseName = '';

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

    protected function setBaseName(string $name): void
    {
        $this->baseName = $name;
        $this->studlyName = Str::studly($this->baseName);
        $this->lowerName = mb_strtolower($this->baseName);
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
    public function getBaseName()
    {
        return $this->baseName;
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
     * @param string $path The string data to apply replaces
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
     * Get the value of lowerName
     *
     * @return  string
     */
    public function getLowerName()
    {
        return $this->lowerName;
    }
}
