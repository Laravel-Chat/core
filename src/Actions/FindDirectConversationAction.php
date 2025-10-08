<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Find existing direct conversation between two users.
 * Follows Single Responsibility Principle - one action, one purpose.
 */
final readonly class FindDirectConversationAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    /**
     * Find existing direct conversation between two users.
     */
    public function handle(Model $user1, Model $user2): ?Conversation
    {
        $conversationModel = $this->config->getConversationModel();

        return $conversationModel::query()
            ->where('type', 'direct')
            ->whereHas('participants', function (Builder $q) use ($user1): void {
                $q->where('user_id', $user1->getKey());
            })
            ->whereHas('participants', function (Builder $q) use ($user2): void {
                $q->where('user_id', $user2->getKey());
            })
            ->has('participants', '=', 2)
            ->first();
    }
}
