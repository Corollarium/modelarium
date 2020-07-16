<?php

namespace App\Listeners;

use App\Events\{|studlyName|}Event;
use App\Notifications\{|studlyName|}Notification;
use Illuminate\Support\Facades\Notification;

class {|studlyName|}Listener
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
    public function handle({|studlyName|}Event $event)
    {
        $n = new {|studlyName|}Notification($event->data);
        // Notification::send($event->data->user, $n);
    }
}
