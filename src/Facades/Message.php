<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Facades;

use Akira\LaravelChat\Models\Message as MessageModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for message operations.
 *
 * @method static MessageModel send(Model $user, int $conversationId, string $content, string $type = 'text', ?array $metadata = null)
 * @method static array getConversationMessages(Model $user, int $conversationId)
 * @method static void markAsRead(Model $user, int $conversationId, array $messageIds = [])
 * @method static void delete(int $messageId, Model $user)
 *
 * @see \Akira\LaravelChat\Support\MessageManager
 */
final class Message extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'chat.message';
    }
}
