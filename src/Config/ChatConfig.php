<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Config;

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\ConversationParticipant;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Policies\MessagePolicyContract;

final class ChatConfig
{
    private static ?self $instance = null;

    private function __construct(
        private readonly string $userModel,
        private readonly string $conversationModel,
        private readonly string $messageModel,
        private readonly string $conversationParticipantModel,
        private readonly string $conversationsTable,
        private readonly string $messagesTable,
        private readonly string $conversationParticipantsTable,
        private readonly bool $broadcastingEnabled,
        private readonly string $broadcastingChannelPrefix,
        private readonly int $conversationsPerPage,
        private readonly int $messagesPerPage,
        private readonly array $messageTypes,
        private readonly array $conversationTypes,
        private readonly ?MessagePolicyContract $messagePolicy,
    ) {}

    public static function getInstance(): self
    {
        if (!self::$instance instanceof \Akira\LaravelChat\Config\ChatConfig) {
            self::$instance = self::fromConfig();
        }

        return self::$instance;
    }

    public static function fromConfig(): self
    {
        $messagePolicyClass = config('chat.policies.message_policy');
        $messagePolicy = $messagePolicyClass ? app($messagePolicyClass) : null;

        return new self(
            userModel: config('chat.user_model', 'App\\Models\\User'),
            conversationModel: config('chat.models.conversation', Conversation::class),
            messageModel: config('chat.models.message', Message::class),
            conversationParticipantModel: config('chat.models.conversation_participant', ConversationParticipant::class),
            conversationsTable: config('chat.tables.conversations', 'conversations'),
            messagesTable: config('chat.tables.messages', 'messages'),
            conversationParticipantsTable: config('chat.tables.conversation_participants', 'conversation_participants'),
            broadcastingEnabled: config('chat.broadcasting.enabled', true),
            broadcastingChannelPrefix: config('chat.broadcasting.channel_prefix', 'chat'),
            conversationsPerPage: config('chat.pagination.conversations_per_page', 20),
            messagesPerPage: config('chat.pagination.messages_per_page', 50),
            messageTypes: config('chat.message_types', ['text', 'image', 'file', 'system']),
            conversationTypes: config('chat.conversation_types', ['direct', 'group']),
            messagePolicy: $messagePolicy,
        );
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function getUserModel(): string
    {
        return $this->userModel;
    }

    public function getConversationModel(): string
    {
        return $this->conversationModel;
    }

    public function getMessageModel(): string
    {
        return $this->messageModel;
    }

    public function getConversationParticipantModel(): string
    {
        return $this->conversationParticipantModel;
    }

    public function getConversationsTable(): string
    {
        return $this->conversationsTable;
    }

    public function getMessagesTable(): string
    {
        return $this->messagesTable;
    }

    public function getConversationParticipantsTable(): string
    {
        return $this->conversationParticipantsTable;
    }

    public function isBroadcastingEnabled(): bool
    {
        return $this->broadcastingEnabled;
    }

    public function getBroadcastingChannelPrefix(): string
    {
        return $this->broadcastingChannelPrefix;
    }

    public function getConversationsPerPage(): int
    {
        return $this->conversationsPerPage;
    }

    public function getMessagesPerPage(): int
    {
        return $this->messagesPerPage;
    }

    public function getMessageTypes(): array
    {
        return $this->messageTypes;
    }

    public function getConversationTypes(): array
    {
        return $this->conversationTypes;
    }

    public function getMessagePolicy(): ?MessagePolicyContract
    {
        return $this->messagePolicy;
    }

    public function isValidMessageType(string $type): bool
    {
        return in_array($type, $this->messageTypes, true);
    }

    public function isValidConversationType(string $type): bool
    {
        return in_array($type, $this->conversationTypes, true);
    }
}
