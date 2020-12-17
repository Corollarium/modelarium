<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Modelarium\Exception\Exception;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

trait WriterTrait
{
    /**
     * Write a GeneractedCollection to the filesystem
     *
     * @param GeneratedCollection $collection
     * @param string $basepath
     * @param boolean|callable $overwrite. If a callable expects (GeneratedItem $item): bool
     * @return array The written files with their full path.
     */
    public function writeFiles(GeneratedCollection $collection, string $basepath, $overwrite = true): array
    {
        $writtenFiles = [];
        foreach ($collection as $element) {
            /**
             * @var GeneratedItem $element
             */
            
            $path = $basepath . '/' . $element->filename;
            $o = false;
            if ($element->onlyIfNewFile) {
                $o = false;
            } elseif (is_bool($overwrite)) {
                $o = $overwrite;
            } elseif (is_callable($overwrite)) {
                $o = $overwrite($element);
            } else {
                throw new Exception("Invalid overwrite value on writeFiles");
            }
            $this->writeFile(
                $path,
                $o,
                $element->contents
            );
            $writtenFiles[] = $path;
        }
        return $writtenFiles;
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
            $this->comment("File $targetPath already exists, not overwriting.");
            return;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            \Safe\mkdir($dir, 0777, true);
        }

        $ret = \Safe\file_put_contents($targetPath, $data);
        if (!$ret) {
            $this->error("Cannot write to $targetPath");
            return;
        }
        $this->line("Wrote $targetPath");
    }
}
