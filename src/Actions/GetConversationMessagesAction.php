<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Support\ValueObjects\MessageCollection;
use Akira\LaravelChat\Support\ValueObjects\MessageResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class GetConversationMessagesAction
{
    /**
     * Get new instance.
     */
    public function __construct(
        private GetAvatarAction $getAvatarAction
    ) {}

    /**
     * Get conversation with its messages for a user.
     *
     * @throws ModelNotFoundException
     */
    public function handle(Model $user, int $conversationId): MessageCollection
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::query()
            ->whereHas('participants', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->getAttribute('id'));
            })
            ->findOrFail($conversationId);

        $messagesCollection = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        /** @var \Illuminate\Support\Collection<int, MessageResult> $messages */
        $messages = $messagesCollection->map(fn (Message $message): MessageResult => $this->formatMessage($message));

        return new MessageCollection($messages, $conversationId);
    }

    /**
     * Format a message for value object.
     */
    private function formatMessage(Message $message): MessageResult
    {
        /** @var Model $user */
        $user = $message->user;

        return new MessageResult(
            id: (int) $message->id,
            conversationId: (int) $message->conversation_id,
            userId: $message->user_id,
            content: (string) $message->content,
            type: (string) $message->type,
            metadata: $message->metadata,
            readAt: $message->read_at?->toIso8601String(),
            createdAt: $message->created_at->toIso8601String(),
            user: [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $this->getAvatarAction->handle($user),
            ]
        );
    }
}
