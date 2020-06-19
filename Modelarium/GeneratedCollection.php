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
}
