<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class MarkMessagesAsReadAction
{
    /**
     * Mark messages as read for a user in a conversation.
     *
     * @param  array<int>  $messageIds
     *
     * @throws ModelNotFoundException
     */
    public function handle(Model $user, int $conversationId, array $messageIds = []): int
    {

        /** @var Conversation $conversation */
        $conversation = Conversation::query()
            ->whereHas('participants', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->getAttribute('id'));
            })
            ->findOrFail($conversationId);

        /** @var Builder<Message> $query */
        $query = Message::query()
            ->where('conversation_id', $conversation->getAttribute('id'))
            ->where('user_id', '!=', $user->getAttribute('id'))
            ->whereNull('read_at');

        if ($messageIds !== []) {
            $query->whereIn('id', $messageIds);
        }

        /** @var int $updatedCount */
        $updatedCount = $query->update(['read_at' => now()]);

        $conversation->participants()->updateExistingPivot($user->getAttribute('id'), [
            'last_read_at' => now(),
        ]);

        return $updatedCount;
    }
}
