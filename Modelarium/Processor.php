<?php declare(strict_types=1);

namespace Modelarium;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use Modelarium\Laravel\Targets\MigrationGenerator;

class Processor
{
    public function processFiles(array $files)
    {
        foreach ($files as $file) {
        }
    }

    /**
     *
     * @param string $data
     * @return GeneratedCollection
     */
    public function processString(string $data): GeneratedCollection
    {
        $parser = Parser::fromString($data);
        $schema = $parser->getSchema();
        $typeMap = $schema->getTypeMap();

        $data = new GeneratedCollection();
        foreach ($typeMap as $name => $object) {
            if ($object instanceof ObjectType) {
                $g = $this->processType($name, $object);
                if ($g) {
                    $data->push($g);
                }
            }
        }

        // TODO $this->processMutation($schema->getMutationType());
        return $data;
    }

    protected function processType(string $name, ObjectType $object): ?GeneratedItem
    {
        if (str_starts_with($name, '__')) {
            // internal type
            return null;
        }

        $gen = new MigrationGenerator($name, $object);
        return new GeneratedItem(
            GeneratedItem::TYPE_MIGRATION,
            $gen->generateString(),
            $gen->getGenerateFilename()
        );
    }

    protected function processMutation(?Type $object)
    {
        if (!$object) {
            return;
        }
    }

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
