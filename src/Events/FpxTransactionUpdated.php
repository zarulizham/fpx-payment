<?php

namespace JagdishJP\FpxPayment\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use JagdishJP\FpxPayment\Models\FpxTransaction;

class FpxTransactionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fpxTransaction;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(FpxTransaction $fpxTransaction)
    {
        $this->fpxTransaction = $fpxTransaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
