<?php declare(strict_types=1);

namespace Modelarium;

class GeneratedItem
{
    const TYPE_EVENT = 'event';
    const TYPE_FACTORY = 'factory';
    const TYPE_MIGRATION = 'migration';
    const TYPE_MODEL = 'model';
    const TYPE_POLICY = 'policy';
    const TYPE_SEED = 'seed';
    const TYPE_FRONTEND = 'frontend';

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var string|callable
     */
    public $contents;

    /**
     * @var boolean
     */
    public $onlyIfNewFile;

    /**
     * Undocumented function
     *
     * @param string $type
     * @param string|callable $contents
     * @param string $filename
     * @param boolean $onlyIfNewFile
     */
    public function __construct(string $type, $contents, string $filename, bool $onlyIfNewFile = false)
    {
        $this->type = $type;
        $this->contents = $contents;
        $this->filename = $filename;
        $this->onlyIfNewFile = $onlyIfNewFile;
    }
}
