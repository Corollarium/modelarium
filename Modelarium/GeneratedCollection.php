<?php declare(strict_types=1);

namespace Modelarium;

use Illuminate\Support\Collection;

class GeneratedCollection extends Collection
{
    public function __construct(array $items = [])
    {
        parent::__construct($items, \ArrayObject::ARRAY_AS_PROPS);
    }

    public function filterByType($type)
    {
        return $this->filter(
            function ($i) use ($type) {
                return $i->type == $type;
            }
        );
    }
}
