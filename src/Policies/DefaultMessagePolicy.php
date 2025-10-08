<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Default message policy - allows all messages.
 * Override with custom policy in config/chat.php.
 */
final class DefaultMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Default: allow all messages
        return true;
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Default: allow all messages
        return true;
    }
}
