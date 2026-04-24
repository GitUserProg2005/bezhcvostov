<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CounterUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $count
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('counter'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'counter.updated';
    }

    public function broadcastWith(): array
    {
        return ['count' => $this->count];
    }
}
