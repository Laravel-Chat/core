<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Policies;

use Illuminate\Database\Eloquent\Model;

interface MessagePolicyContract
{
    /**
     * Determine if the sender can send a message to the recipient.
     */
    public function canSendMessage(Model $sender, Model $recipient): bool;

    /**
     * Determine if the user can receive messages from the sender.
     */
    public function canReceiveMessage(Model $recipient, Model $sender): bool;
}
