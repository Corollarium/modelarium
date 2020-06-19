<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use Modelarium\Laravel\Targets\MigrationGenerator;

abstract class Processor
{
    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    abstract public function processString(string $data): GeneratedCollection;

    /**
     * Takes a stub file and generates the target file with replacements.
     *
     * @param string $targetPath The path for the stub file.
     * @param boolean $overwrite
     * @param string $data The data to write
     * @return void
     */
    protected function writeFile(string $targetPath, bool $overwrite, string $data)
    {
        if (file_exists($targetPath) && !$overwrite) {
            // $this->warning("File $targetPath already exists.");
            return;
        }

        mkdir(dirname($targetPath), 0777, true);

        $ret = file_put_contents($targetPath, $data);
        if (!$ret) {
            throw new \Exception("Cannot write to $targetPath");
        }
    }
}
