<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Doctrine\Inflector\InflectorFactory;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\Parser;

abstract class BaseGenerator
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

    /**
     * @var string
     */
    protected $stubDir = __DIR__ . "/stubs/";

    /**
     * @var \Doctrine\Inflector\Inflector
     */
    protected $inflector = null;

    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @var Type
     */
    protected $type = null;

    /**
     * @param Parser $parser
     * @param string $name
     * @param Type|string $type
     */
    public function __construct(Parser $parser, string $name, $type = null)
    {
        $this->inflector = InflectorFactory::create()->build();

        $this->parser = $parser;
        $this->setName($name);

        if ($type instanceof Type) {
            $this->type = $type;
        } elseif (!$type) {
            $this->type = $parser->getSchema()->getType($name);
        } else {
            throw new Exception('Invalid model');
        }
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
        $this->studlyName = Str::studly($this->name);
        $this->lowerName = mb_strtolower($this->name);
        $this->lowerNamePlural = $this->inflector->pluralize($this->lowerName);
    }

    protected function splitClassName(string $fullclass): array
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

    abstract public function generate(): GeneratedCollection;

    /**
     * Stubs from a stub file.
     *
     * @param string $stubName The name for the stub file.
     * @param Callable $f
     * @return string
     * @see BaseGenerator::stubFile()
     * @throws \Safe\Exceptions\FilesystemException
     */
    public function stubToString(string $stubName, callable $f = null): string
    {
        $stub = \Safe\file_get_contents($this->stubDir . "/$stubName.stub.php");
        return $this->replaceStub($stub, $f);
    }

    /**
     * Stubs a string.
     *
     * @param string $stub
     * @param callable $f
     * @return string
     */
    public function replaceStub(string $stub, callable $f = null): string
    {
        $data = $this->replaceDummy($stub);
        if ($f) {
            $data = $f($data);
        }
        return $data;
    }

    /**
     * Replaces common strings from the stubs
     *
     * @param string $str The string data to apply replaces
     * @return string
     */
    protected function replaceDummy(string $str)
    {
        $str = str_replace(
            'DummyClass',
            $this->studlyName,
            $str
        );
        $str = str_replace(
            'DummyName',
            $this->name,
            $str
        );
        $str = str_replace(
            'dummynameplural',
            $this->lowerNamePlural,
            $str
        );
        $str = str_replace(
            'dummyname',
            $this->lowerName,
            $str
        );
        return $str;
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
}
