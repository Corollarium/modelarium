<?php declare(strict_types=1);

namespace Modelarium;

use ArrayAccess;

use function Safe\file_get_contents;
use function Safe\getcwd;
use function Safe\json_decode;
use function Safe\substr;

class Options
{
    /**
     * @var string
     */
    public $basePath = null;

    /**
     *
     * @var array
     */
    public $options = [];

    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath;
        $this->options = $this->loadOptions();
    }

    /**
     * Gets a value
     *
     * @param string $section
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $section, string $key, $default = null)
    {
        return $this->options[$section][$key] ?? $default;
    }

    public function getSection(string $section): array
    {
        return $this->options[$section] ?? [];
    }

    public function setSectionDefaults(string $section, array $defaults): self
    {
        if (!array_key_exists($section, $this->options)) {
            $this->options[$section] = $defaults;
        } else {
            $this->options[$section] = array_merge($defaults, $this->options[$section]);
        }
        return $this;
    }

    /**
     * Returns the base path where we expect to find the options
     *
     * @return string
     */
    public function getBasePath(): string
    {
        if ($this->basePath) {
            return $this->basePath;
        }
        // assume we're in vendor/
        $path = __DIR__;
        $start = mb_strpos('/vendor/', $path);
        if ($start !== false) {
            return substr($path, $start);
        }
        return getcwd();
    }

    protected function loadOptions(): array
    {
        // try json
        $filename = $this->getBasePath() . '/modelarium.json';
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true);
        }

        // try php
        $filename = $this->getBasePath() . '/config/modelarium.php';
        if (file_exists($filename)) {
            return require($filename);
        }

        return [];
    }
}
