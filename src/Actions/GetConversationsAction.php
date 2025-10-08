<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Support\ValueObjects\ConversationCollection;
use Akira\LaravelChat\Support\ValueObjects\ConversationResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final readonly class GetConversationsAction
{
    /**
     * Get new instance.
     */
    public function __construct(
        private GetAvatarAction $getAvatarAction
    ) {}

    /**
     * Get all conversations for a user.
     */
    public function handle(Model $user): ConversationCollection
    {
        $query = $user->conversations();

        $query->with('participants');
        // @phpstan-ignore-next-line
        $query->with(['messages' => fn (HasMany $query) => $query->latest()->limit(1)->with('user')]);

        /** @var Collection<int, Conversation> $conversationsCollection */
        $conversationsCollection = $query->orderBy('last_message_at', 'desc')
            ->get();

        /** @var \Illuminate\Support\Collection<int, ConversationResult> $results */
        $results = $conversationsCollection->map(function (Conversation $conversation) use ($user): ConversationResult {
            /** @var Collection<int, Message> $messages */
            $messages = $conversation->getRelation('messages');
            $lastMessage = $messages->first();

            /** @var Collection<int, Model> $participants */
            $participants = $conversation->getRelation('participants');
            $userId = $user->id;
            $otherParticipants = $participants->where('id', '!=', $userId);

            /** @var Model|null $otherParticipant */
            $otherParticipant = $otherParticipants->first();

            return new ConversationResult(
                id: (int) $conversation->id,
                type: (string) $conversation->type,
                title: $conversation->title ?: $otherParticipants->pluck('name')->join(', '),
                createdBy: $conversation->created_by,
                participants: $participants->map(fn (Model $participant): array => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'avatar_url' => $this->getAvatarAction->handle($participant),
                ])->values()->toArray(),
                lastMessage: ($lastMessage instanceof Message) ? $this->formatMessage($lastMessage) : null,
                lastMessageAt: $conversation->last_message_at?->toIso8601String(),
                unreadCount: $conversation->messages()
                    ->where('user_id', '!=', $userId)
                    ->whereNull('read_at')
                    ->count(),
                avatarUrl: $this->getAvatarAction->handle($otherParticipant),
                otherParticipant: $otherParticipant
            );
        });

        return new ConversationCollection($results);
    }

    /**
     * Format a message for API response.
     *
     * @return array{id: mixed, content: mixed, type: mixed, created_at: mixed, user: array{id: mixed, name: mixed, avatar_url: string|null}}
     */
    private function formatMessage(Message $message): array
    {
        $user = $message->user;

        return [
            'id' => $message->id,
            'content' => $message->content,
            'type' => $message->type,
            'metadata' => $message->metadata,
            'created_at' => $message->created_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $this->getAvatarAction->handle($user),
            ],
        ];
    }
}
