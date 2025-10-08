<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class DeleteConversationAction
{
    /**
     * Delete a conversation (only by creator).
     *
     * @throws ModelNotFoundException|Exception
     */
    public function handle(Model $user, int $conversationId): void
    {
        /** @var Conversation $conversation */
        $conversation = Conversation::query()
            ->whereHas('participants', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->getAttribute('id'));
            })
            ->with('creator')
            ->findOrFail($conversationId);

        /** @var Model $creator */
        $creator = $conversation->getRelation('creator');
        if ($creator->getAttribute('id') !== $user->getAttribute('id')) {
            throw new Exception('Unauthorized');
        }

        $conversation->delete();
    }
}
