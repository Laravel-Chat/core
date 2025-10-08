<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support;

use Akira\LaravelChat\Actions\DeleteMessageAction;
use Akira\LaravelChat\Actions\GetConversationMessagesAction;
use Akira\LaravelChat\Actions\MarkMessagesAsReadAction;
use Akira\LaravelChat\Actions\SendMessageAction;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Support\ValueObjects\MessageCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * Message Manager - Orchestrates message actions.
 * Used by the Message Facade.
 */
final readonly class MessageManager
{
    public function __construct(
        private SendMessageAction $sendAction,
        private GetConversationMessagesAction $getMessagesAction,
        private MarkMessagesAsReadAction $markAsReadAction,
        private DeleteMessageAction $deleteAction,
    ) {}

    /**
     * Send a message to a conversation.
     */
    public function send(
        Model $user,
        int $conversationId,
        string $content,
        string $type = 'text',
        ?array $metadata = null
    ): Message {
        return $this->sendAction->handle($user, $conversationId, $content, $type, $metadata);
    }

    /**
     * Get messages for a conversation.
     */
    public function getConversationMessages(Model $user, int $conversationId): MessageCollection
    {
        return $this->getMessagesAction->handle($user, $conversationId);
    }

    /**
     * Mark messages as read for a user in a conversation.
     *
     * @param  array<int>  $messageIds
     */
    public function markAsRead(Model $user, int $conversationId, array $messageIds = []): void
    {
        $this->markAsReadAction->handle($user, $conversationId, $messageIds);
    }

    /**
     * Delete a message.
     */
    public function delete(int $messageId, Model $user): void
    {
        $this->deleteAction->handle($user, $messageId);
    }
}
