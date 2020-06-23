<?php declare(strict_types=1);

namespace Modelarium;

use Illuminate\Support\Collection;

class GeneratedCollection extends Collection
{
    public function filterByType(string $type): GeneratedCollection
    {
        return $this->filter(
            function ($i) use ($type) {
                return $i->type == $type;
            }
        );
    }

    public function writeFiles(string $basepath, bool $overwrite = true)
    {
        foreach ($this as $element) {
            $path = $basepath . '/' . $element->filename;
            $this->writeFile($path, $overwrite, $element->contents);
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

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ret = file_put_contents($targetPath, $data);
        if (!$ret) {
            throw new \Exception("Cannot write to $targetPath");
        }
    }
}
