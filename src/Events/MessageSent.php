<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Events;

use Akira\LaravelChat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        $conversationId = $this->message->conversation_id;
        $prefix = config('chat.broadcasting.channel_prefix', 'chat');
        $conversationChannelName = "{$prefix}.conversation.{$conversationId}";

        $channels = [
            new PrivateChannel($conversationChannelName),
        ];

        // Also broadcast to all participants' user channels
        $participants = $this->message->conversation->participants;
        foreach ($participants as $participant) {
            $userChannelName = "{$prefix}.user.{$participant->id}";
            $channels[] = new PrivateChannel($userChannelName);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
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
