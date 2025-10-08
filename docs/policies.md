# Message Policies

Learn how to control who can send messages to whom using configurable policies.

## Table of Contents

- [Overview](#overview)
- [Policy Contract](#policy-interface)
- [Built-in Policies](#built-in-policies)
- [Creating Custom Policies](#creating-custom-policies)
- [Configuration](#configuration)
- [Use Cases](#use-cases)
- [Best Practices](#best-practices)

## Overview

Message Policies control who can send and receive messages in your chat application. They provide a flexible, configurable way to implement business rules and privacy settings without modifying core package code.

### Why Policies?

- **Privacy Controls** - Implement user privacy preferences
- **Business Rules** - Enforce application-specific messaging rules
- **Spam Prevention** - Block unwanted messages
- **Compliance** - Meet regulatory requirements
- **Flexibility** - Swap policies without code changes

## Policy Contract

All message policies must implement the `MessagePolicyContract`:

```php
namespace Akira\LaravelChat\Policies;

use Illuminate\Database\Eloquent\Model;

interface MessagePolicyContract
{
    /**
     * Determine if the sender can send messages to the recipient.
     */
    public function canSendMessage(Model $sender, Model $recipient): bool;
    
    /**
     * Determine if the recipient can receive messages from the sender.
     */
    public function canReceiveMessage(Model $recipient, Model $sender): bool;
}
```

### Method Purposes

#### `canSendMessage()`
Checks if the **sender** has permission to send messages. Use this for:
- Sender account restrictions (banned, suspended)
- Rate limiting
- Sender-specific business rules

#### `canReceiveMessage()`
Checks if the **recipient** accepts messages from this sender. Use this for:
- Privacy settings
- Blocking
- Follower/friend requirements
- Do-not-disturb status

## Built-in Policies

### DefaultMessagePolicy

Allows all messages - no restrictions.

```php
class DefaultMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        return true; // Allow all
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        return true; // Allow all
    }
}
```

**Use when:** You want unrestricted messaging or will handle permissions in other layers.

---

### FollowerMessagePolicy

Only allows messages between users who follow each other, or users with existing conversations.

```php
class FollowerMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Allow if following each other OR have existing conversation
        return $this->areFollowingEachOther($sender, $recipient) ||
               $this->haveExistingConversation($sender, $recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check recipient's custom method if available
        if (method_exists($recipient, 'acceptsMessagesFrom')) {
            return $recipient->acceptsMessagesFrom($sender);
        }

        // Default: check mutual following
        return $this->areFollowingEachOther($sender, $recipient);
    }
}
```

**Use when:** Your app has a follower/following system and you want to restrict messaging.

**Requirements:**
- User model must have `follows()` method
- Optionally, `acceptsMessagesFrom()` method for custom logic

---

## Creating Custom Policies

### Step 1: Create Policy Class

```php
namespace App\Policies;

use Akira\LaravelChat\Policies\MessagePolicyContract;
use Illuminate\Database\Eloquent\Model;

class CustomMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Your custom logic
        return true;
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Your custom logic
        return true;
    }
}
```

### Step 2: Register in Configuration

```php
// config/chat.php
'policies' => [
    'message_policy' => \App\Policies\CustomMessagePolicy::class,
]
```

### Step 3: Policy is Applied Automatically

The package automatically checks policies when:
- Creating conversations
- Sending messages

---

## Configuration

### Enable/Disable Policies

```php
// config/chat.php
'policies' => [
    'message_policy' => null, // Disable policies (allow all)
    // OR
    'message_policy' => \App\Policies\CustomMessagePolicy::class, // Enable
]
```

### Multiple Policy Support

You can create different policies for different scenarios:

```php
// Switch policies based on environment or feature flags
'policies' => [
    'message_policy' => env('CHAT_STRICT_MODE') 
        ? \App\Policies\StrictMessagePolicy::class
        : \App\Policies\RelaxedMessagePolicy::class,
]
```

---

## Use Cases

### Use Case 1: Privacy Settings

Allow users to control who can message them:

```php
class PrivacySettingsPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Check if sender's account is active
        return $sender->is_active && !$sender->is_suspended;
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check recipient's privacy setting
        return match ($recipient->message_privacy) {
            'everyone' => true,
            'followers' => $recipient->isFollowedBy($sender),
            'following' => $sender->isFollowedBy($recipient),
            'mutual' => $recipient->isFollowedBy($sender) && 
                        $sender->isFollowedBy($recipient),
            'none' => false,
            default => true,
        };
    }
}
```

**User Model:**
```php
class User extends Model
{
    protected $fillable = ['message_privacy'];
    
    // Options: 'everyone', 'followers', 'following', 'mutual', 'none'
}
```

---

### Use Case 2: Blocking

Prevent blocked users from messaging:

```php
class BlockingPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Sender cannot send to users who blocked them
        return !$recipient->hasBlocked($sender);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Recipient doesn't receive from blocked users
        return !$recipient->hasBlocked($sender);
    }
}
```

**User Model:**
```php
class User extends Model
{
    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_user_id', $user->id)->exists();
    }
    
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }
}
```

---

### Use Case 3: Friend System

Only allow messaging between friends:

```php
class FriendMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        return $this->areFriends($sender, $recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        return $this->areFriends($recipient, $sender);
    }
    
    private function areFriends(Model $user1, Model $user2): bool
    {
        return $user1->friends()->where('friend_id', $user2->id)->exists();
    }
}
```

---

### Use Case 4: Verified Users Only

Require users to be verified:

```php
class VerifiedUsersPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Only verified users can send messages
        return $sender->is_verified ?? false;
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Recipient must also be verified
        return $recipient->is_verified ?? false;
    }
}
```

---

### Use Case 5: Business Hours

Restrict messaging to business hours:

```php
class BusinessHoursPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        return $this->isBusinessHours();
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check recipient's availability
        if ($recipient->accepts_messages_anytime) {
            return true;
        }
        
        return $this->isBusinessHours();
    }
    
    private function isBusinessHours(): bool
    {
        $now = now();
        $hour = $now->hour;
        
        // 9 AM to 5 PM on weekdays
        return !$now->isWeekend() && $hour >= 9 && $hour < 17;
    }
}
```

---

### Use Case 6: Role-Based Policies

Different rules for different user roles:

```php
class RoleBasedPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Admins can always send
        if ($sender->hasRole('admin')) {
            return true;
        }
        
        // Premium users can send to anyone
        if ($sender->hasRole('premium')) {
            return true;
        }
        
        // Free users can only message friends
        return $sender->isFriendsWith($recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Users can always receive from admins
        if ($sender->hasRole('admin')) {
            return true;
        }
        
        // Check recipient's preference
        return $recipient->message_preference !== 'none';
    }
}
```

---

## Best Practices

### 1. Keep Policies Focused

```php
// ✅ Good - Single responsibility
class BlockingPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        return !$recipient->hasBlocked($sender);
    }
    
    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        return !$recipient->hasBlocked($sender);
    }
}

// ❌ Bad - Too many responsibilities
class ComplexPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Checks blocking, friends, followers, verification, rate limiting...
        // Too complex!
    }
}
```

### 2. Consider Performance

```php
// ✅ Good - Efficient database queries
class OptimizedPolicy implements MessagePolicyContract
{
    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Single query with index
        return !DB::table('blocked_users')
            ->where('user_id', $recipient->id)
            ->where('blocked_user_id', $sender->id)
            ->exists();
    }
}

// ❌ Bad - N+1 queries
class SlowPolicy implements MessagePolicyContract
{
    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        foreach ($recipient->blockedUsers as $blocked) {
            if ($blocked->id === $sender->id) {
                return false;
            }
        }
        return true;
    }
}
```

### 3. Handle Edge Cases

```php
// ✅ Good - Handles missing methods gracefully
class SafePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        if (!method_exists($sender, 'isFollowing')) {
            return true; // Graceful fallback
        }
        
        return $sender->isFollowing($recipient);
    }
}
```

### 4. Test Your Policies

```php
use Akira\LaravelChat\Facades\Conversation;

test('blocked user cannot send message', function () {
    $user = User::factory()->create();
    $blocked = User::factory()->create();
    
    $user->blockUser($blocked);
    
    expect(fn() => Conversation::create($blocked, 'direct', [$user->id]))
        ->toThrow(Exception::class, 'User does not accept messages');
});
```

### 5. Document Your Policies

```php
/**
 * Privacy-aware message policy.
 * 
 * Respects user privacy settings:
 * - 'everyone': Anyone can message
 * - 'followers': Only followers can message
 * - 'none': No one can message
 * 
 * Requires User model to have:
 * - message_privacy column (string)
 * - isFollowedBy(User $user) method
 */
class PrivacySettingsPolicy implements MessagePolicyContract
{
    // ...
}
```

---

## Combining Policies

For complex scenarios, use composition:

```php
class CompositePolicy implements MessagePolicyContract
{
    public function __construct(
        private BlockingPolicy $blockingPolicy,
        private VerifiedUsersPolicy $verifiedPolicy,
        private PrivacySettingsPolicy $privacyPolicy,
    ) {}

    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Check all policies
        return $this->blockingPolicy->canSendMessage($sender, $recipient) &&
               $this->verifiedPolicy->canSendMessage($sender, $recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check all policies
        return $this->blockingPolicy->canReceiveMessage($recipient, $sender) &&
               $this->verifiedPolicy->canReceiveMessage($recipient, $sender) &&
               $this->privacyPolicy->canReceiveMessage($recipient, $sender);
    }
}
```

---

## Error Handling

Policies throw exceptions that you can catch and handle:

```php
use Akira\LaravelChat\Facades\Conversation;

try {
    $result = Conversation::create($user, 'direct', [$recipientId]);
} catch (Exception $e) {
    // Handle policy rejection
    if (str_contains($e->getMessage(), 'does not accept messages')) {
        return response()->json([
            'error' => 'This user does not accept messages from you',
            'reason' => 'privacy_settings'
        ], 403);
    }
    
    throw $e;
}
```

---

## Navigation

← [Previous: value-objects.md](value-objects.md) | [Index](INDEX.md) | [Next: broadcasting.md](broadcasting.md) →
