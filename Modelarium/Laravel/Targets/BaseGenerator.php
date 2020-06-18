<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\Parser;

abstract class BaseGenerator
{
    protected $targetName = '';

    protected $studlyName = '';

    protected $lowerName = '';

    protected $lowerNamePlural = '';

    protected $stubDir = __DIR__ . "/stubs/";

    protected $inflector = null;

    /**
     * @var Parser
     */
    protected $model = null;

    public function __construct($name, $model = null)
    {
        $this->inflector = InflectorFactory::create()->build();

        $this->targetName = $name;
        $this->studlyName = Str::studly($this->targetName);
        $this->lowerName = mb_strtolower($this->targetName);
        $this->lowerNamePlural = $this->inflector->pluralize($this->lowerName);
        if ($model instanceof Parser) {
            // ok
        } elseif ($model instanceof string) {
            $parser = Parser::fromString($model);
        } else {
            throw new Exception('Invalid model');
        }
        $this->model = $model;
    }

    /**
     * Returns the base path (where composer.json is located)
     *
     * @param string $file The filename
     * @return string
     */
    public function getBasePath(string $file = null): string
    {
        $basepath = dirname(\Composer\Factory::getComposerFile());
        if ($file) {
            $basepath .= '/' . $file;
        }
        return $basepath;
    }

    abstract protected function getGenerateFilename(): string;

    /**
     * Undocumented function
     *
     * @return string
     */
    abstract public function generateString(): string;

    public function generateFile($overwrite = true)
    {
        $data = $this->generateString();
        $path = $this->getGenerateFilename(); // order matters, after $generateString
        return $this->writeStub($path, $overwrite, $path);
    }

    /**
     * Prints a warning message.
     *
     * @param string $message
     * @return void
     */
    protected function warning(string $message)
    {
        echo 'WARNING: ' . $message;
    }

    protected function error(string $message)
    {
        echo 'ERROR: ' . $message;
    }

    protected function line(string $message)
    {
        echo $message;
    }

    /**
     * Takes a stub file and generates the target file with replacements.
     *
     * @param string $targetPath The path for the stub file.
     * @param boolean $overwrite
     * @param string $stubName The name for the stub file.
     * @return void
     */
    public function writeStub(string $targetPath, bool $overwrite, string $stubData)
    {
        if (file_exists($targetPath) && !$overwrite) {
            $this->warning("File $targetPath already exists.");
            return;
        }

        mkdir(dirname($targetPath), 0777, true);

        $ret = file_put_contents($targetPath, $stubData);
        if (!$ret) {
            $this->error("Cannot write to $targetPath");
            throw new \Exception("Cannot write to $targetPath");
        }
        $this->line("Generated $targetPath");
    }

    /**
     * Stubs from a stub file.
     *
     * @param string $stubName The name for the stub file.
     * @param Callable $f
     * @return string
     * @see BaseGenerator::stubFile()
     */
    public function stubToString(string $stubName, callable $f = null): string
    {
        $stub = file_get_contents($this->stubDir . "/$stubName.stub.php");
        if ($stub === false) {
            throw new \Exception('Stub file not found');
        }
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
            $this->targetName,
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
}
