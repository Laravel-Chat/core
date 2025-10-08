<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Example policy: Only allow messages between users who follow each other.
 * This is an example - customize based on your application's needs.
 */
final class FollowerMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Check if sender follows recipient OR if they're already in a conversation
        if ($this->areFollowingEachOther($sender, $recipient)) {
            return true;
        }
        return $this->haveExistingConversation($sender, $recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check if recipient accepts messages from sender
        if (method_exists($recipient, 'acceptsMessagesFrom')) {
            return $recipient->acceptsMessagesFrom($sender);
        }

        // Default: check if they follow each other
        return $this->areFollowingEachOther($sender, $recipient);
    }

    private function areFollowingEachOther(Model $user1, Model $user2): bool
    {
        // Example: Check if users follow each other
        // Customize this based on your follower system
        if (! method_exists($user1, 'follows') || ! method_exists($user2, 'follows')) {
            return true; // Fallback to allowing if no follower system
        }

        return $user1->follows($user2) && $user2->follows($user1);
    }

    private function haveExistingConversation(Model $user1, Model $user2): bool
    {
        // If users already have a conversation, allow messages
        if (! method_exists($user1, 'conversations')) {
            return false;
        }

        return $user1->conversations()
            ->whereHas('participants', function ($query) use ($user2): void {
                $query->where('user_id', $user2->getKey());
            })
            ->exists();
    }
}
