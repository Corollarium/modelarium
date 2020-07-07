<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Modelarium\GeneratedCollection;

trait WriterTrait
{
    public function writeFiles(GeneratedCollection $collection, string $basepath, bool $overwrite = true): self
    {
        foreach ($collection as $element) {
            /**
             * @var GeneratedItem $element
             */
            $path = $basepath . '/' . $element->filename;
            $this->writeFile(
                $path,
                ($element->onlyIfNewFile ? false : $overwrite),
                $element->contents
            );
        }
        return $this;
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
