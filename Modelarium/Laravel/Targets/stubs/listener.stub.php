<?php

namespace App\Listeners;

use App\Events\DummyClassEvent;
use App\Notifications\DummyClassNotification;
use Illuminate\Support\Facades\Notification;

class DummyClassListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DummyClassEvent $event)
    {
        $n = new DummyClassNotification($event->data);
        // Notification::send($event->data->user, $n);
    }
}
