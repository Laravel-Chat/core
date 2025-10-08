<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Traits;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasConversations
{
    /**
     * Get the conversations the user is part of.
     *
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        $config = ChatConfig::getInstance();
        $conversationModel = $config->getConversationModel();
        $pivotTable = $config->getConversationParticipantsTable();

        return $this->belongsToMany($conversationModel, $pivotTable)
            ->withPivot(['joined_at', 'last_read_at', 'is_admin'])
            ->withTimestamps()
            ->orderByDesc('last_message_at');
    }

    /**
     * Get unread messages count for the user.
     */
    public function unreadMessagesCount(): int
    {
        $config = ChatConfig::getInstance();
        $messageModel = $config->getMessageModel();

        return $messageModel::query()
            ->whereHas('conversation', function ($query): void {
                $query->whereHas('participants', function ($q): void {
                    $q->where('user_id', $this->id);
                });
            })
            ->where('user_id', '!=', $this->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Check if this user can receive messages from another user.
     * Uses the configured message policy.
     */
    public function canReceiveMessagesFrom(Model $sender): bool
    {
        $config = ChatConfig::getInstance();
        $policy = $config->getMessagePolicy();

        // If no policy is configured, allow all messages
        if (!$policy instanceof \Akira\LaravelChat\Policies\MessagePolicyContract) {
            return true;
        }

        return $policy->canReceiveMessage($this, $sender);
    }

    /**
     * Check if this user can send messages to another user.
     * Uses the configured message policy.
     */
    public function canSendMessagesTo(Model $recipient): bool
    {
        $config = ChatConfig::getInstance();
        $policy = $config->getMessagePolicy();

        // If no policy is configured, allow all messages
        if (!$policy instanceof \Akira\LaravelChat\Policies\MessagePolicyContract) {
            return true;
        }

        return $policy->canSendMessage($this, $recipient);
    }
}
