<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Events;

use Akira\LaravelChat\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConversationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Model $user
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        $prefix = config('chat.broadcasting.channel_prefix', 'chat');
        $userChannelName = "{$prefix}.user.{$this->user->id}";

        return [
            new PrivateChannel($userChannelName),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    /**
     * The event's broadcast data.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'title' => $this->conversation->title,
                'type' => $this->conversation->type,
                'created_at' => $this->conversation->created_at,
            ],
        ];
    }
}
