<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support;

use Akira\LaravelChat\Actions\CreateConversationAction;
use Akira\LaravelChat\Actions\DeleteConversationAction;
use Akira\LaravelChat\Actions\FindDirectConversationAction;
use Akira\LaravelChat\Actions\GetConversationsAction;
use Akira\LaravelChat\Actions\ValidateUserIsParticipantAction;
use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Support\ValueObjects\ConversationCollection;
use Akira\LaravelChat\Support\ValueObjects\CreateConversationResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Conversation Manager - Orchestrates conversation actions.
 * Used by the Conversation Facade.
 */
final readonly class ConversationManager
{
    public function __construct(
        private CreateConversationAction $createAction,
        private GetConversationsAction $getConversationsAction,
        private DeleteConversationAction $deleteAction,
        private ValidateUserIsParticipantAction $validateAction,
        private FindDirectConversationAction $findDirectAction,
    ) {}

    /**
     * Create a new conversation.
     *
     * @param  string  $type  'direct' or 'group'
     * @param  array<int>  $participantIds
     */
    public function create(Model $creator, string $type, array $participantIds, ?string $title = null): CreateConversationResult
    {
        return $this->createAction->handle($creator, $type, $participantIds, $title);
    }

    /**
     * Get all conversations for a user.
     */
    public function getUserConversations(Model $user): ConversationCollection
    {
        return $this->getConversationsAction->handle($user);
    }

    /**
     * Get or create a direct conversation between two users.
     *
     * @throws Throwable
     */
    public function getOrCreateDirect(Model $user1, Model $user2): ?Conversation
    {
        // Check if conversation exists using dedicated action
        $existing = $this->findDirectAction->handle($user1, $user2);

        if ($existing instanceof \Akira\LaravelChat\Models\Conversation) {
            return $existing;
        }

        // Create new conversation
        $result = $this->createAction->handle($user1, 'direct', [$user2->getKey()]);

        // Use config to get model class
        $conversationModel = app(ChatConfig::class)->getConversationModel();

        return $conversationModel::find($result->id);
    }

    /**
     * Delete a conversation.
     */
    public function delete(int $conversationId, Model $user): void
    {
        $this->deleteAction->handle($user, $conversationId);
    }

    /**
     * Validate if a user is a participant of a conversation.
     */
    public function validateParticipant(int $conversationId, Model $user): bool
    {
        try {
            $this->validateAction->handle($user, $conversationId);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
