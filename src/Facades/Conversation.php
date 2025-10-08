<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Facades;

use Akira\LaravelChat\Actions\CreateConversationAction;
use Akira\LaravelChat\Actions\DeleteConversationAction;
use Akira\LaravelChat\Actions\GetConversationsAction;
use Akira\LaravelChat\Actions\ValidateUserIsParticipantAction;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for conversation operations.
 *
 * @method static array create(Model $creator, string $type, array $participantIds, ?string $title = null)
 * @method static array getUserConversations(Model $user)
 * @method static ConversationModel|null getOrCreateDirect(Model $user1, Model $user2)
 * @method static void delete(int $conversationId, Model $user)
 * @method static bool validateParticipant(int $conversationId, Model $user)
 *
 * @see \Akira\LaravelChat\Support\ConversationManager
 */
final class Conversation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'chat.conversation';
    }
}
