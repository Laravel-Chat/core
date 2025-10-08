<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Events;

use Akira\LaravelChat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public Model $forUser
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        $userId = $this->forUser->id;
        $prefix = config('chat.broadcasting.channel_prefix', 'chat');
        $channelName = "{$prefix}.user.{$userId}";

        return [
            new PrivateChannel($channelName),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.received';
    }

    /**
     * The event's broadcast data.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $message = $this->message;
        $user = $message->user;

        return [
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'metadata' => $message->metadata,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name ?? 'Unknown',
                ],
            ],
            'conversation_id' => $message->conversation_id,
        ];
    }
}
