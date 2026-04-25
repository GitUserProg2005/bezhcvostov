<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('projects.chat.'.$this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'chat_id' => $this->message->chat_id,
                'created_at' => $this->message->created_at?->toIso8601String(),
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                    'avatar_url' => $this->message->user->avatar_url,
                ],
            ],
        ];
    }
}
