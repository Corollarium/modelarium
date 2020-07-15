<?php declare(strict_types=1);

namespace Modelarium;

use Doctrine\Inflector\InflectorFactory;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\Parser;

abstract class BaseGenerator implements GeneratorInterface
{
    use GeneratorNameTrait;

    /**
     * @var string
     */
    protected $stubDir = null;

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
     * @param string $name The target type name.
     * @param Type|string $type
     */
    public function __construct(Parser $parser, string $name, $type = null)
    {
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
     * @deprecated prefer template() from GeneratorNameTrait
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
}
