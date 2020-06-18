<?php

namespace DummyEventNamespace;

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
    public function __construct(DummyClass $target)
    {
        $this->target = $target;
    }
}
