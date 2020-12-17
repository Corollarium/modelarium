<?php declare(strict_types=1);

namespace Modelarium\Laravel\Targets;

class MigrationCodeFragment
{
    /**
     * Unique counter
     *
     * @var string
     */
    public $base = '';

    /**
     * @var string[]
     */
    public $extraLines = [];

    public function appendBase(string $s): void
    {
        $this->base .= $s;
    }

    public function appendExtraLine(string $s): void
    {
        $this->extraLines[] = $s;
    }
}
