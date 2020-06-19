<?php

namespace DummyEventNamespace;

use App\Models\DummyTypeClass;
use Illuminate\Queue\SerializesModels;

class DummyEventClassName
{
    use SerializesModels;

    public $target;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(DummyTypeClass $target)
    {
        $this->target = $target;
    }
}
