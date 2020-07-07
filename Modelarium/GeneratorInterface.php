<?php declare(strict_types=1);

namespace Modelarium;

use Modelarium\GeneratedCollection;

interface GeneratorInterface
{
    /**
     * Returns a collection of items generated
     *
     * @return GeneratedCollection
     */
    public function generate(): GeneratedCollection;
}
