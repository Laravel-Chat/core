# Action Pattern

The Action Pattern is a core architectural pattern in the Laravel Chat Package. This document explains what actions are, why we use them, and how to work with them.

## What are Actions?

Actions are single-purpose classes that encapsulate a specific piece of business logic. Each action does one thing and does it well.

```php
class SendMessageAction
{
    public function handle(
        Model $user,
        int $conversationId,
        string $content,
        string $type = 'text',
        ?array $metadata = null
    ): Message {
        // Single responsibility: Send a message
    }
}
```

## Why Use Actions?

### 1. Single Responsibility

Each action has exactly one job:

```php
CreateConversationAction      // Creates conversations
SendMessageAction            // Sends messages
DeleteMessageAction          // Deletes messages
GetConversationsAction       // Retrieves conversations
MarkMessagesAsReadAction     // Marks messages as read
```

### 2. Reusability

Actions can be used from multiple entry points:

```php
// From a controller
$message = app(SendMessageAction::class)->handle($user, $conversationId, 'Hello');

// From a command
$message = $this->sendMessageAction->handle($user, $conversationId, 'Hello');

// From a job
$message = $action->handle($user, $conversationId, 'Hello');

// Through the facade
$message = Message::send($user, $conversationId, 'Hello');
```

### 3. Testability

Actions are easy to test in isolation:

```php
test('sends message to conversation', function () {
    $action = new SendMessageAction($config);
    
    $message = $action->handle($user, $conversation->id, 'Test message');
    
    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->content)->toBe('Test message');
});
```

### 4. Composability

Actions can be composed together:

```php
class CreateConversationAction
{
    public function __construct(
        private ChatConfig $config,
        private FindDirectConversationAction $findDirectAction  // Compose actions
    ) {}

    public function handle(Model $creator, string $type, array $participantIds)
    {
        // Use another action
        $existing = $this->findDirectAction->handle($creator, $otherUser);
        
        if ($existing) {
            return new CreateConversationResult(/*...*/);
        }
        
        // Create new conversation
    }
}
```

## Package Actions

### Conversation Actions

**CreateConversationAction**
```php
public function handle(
    Model $creator,
    string $type,
    array $participantIds,
    ?string $title = null
): CreateConversationResult
```

**GetConversationsAction**
```php
public function handle(Model $user): ConversationCollection
```

**DeleteConversationAction**
```php
public function handle(Model $user, int $conversationId): void
```

**FindDirectConversationAction**
```php
public function handle(Model $user1, Model $user2): ?Conversation
```

**ValidateUserIsParticipantAction**
```php
public function handle(Model $user, int $conversationId): void
```

### Message Actions

**SendMessageAction**
```php
public function handle(
    Model $user,
    int $conversationId,
    string $content,
    string $type = 'text',
    ?array $metadata = null
): Message
```

**GetConversationMessagesAction**
```php
public function handle(Model $user, int $conversationId): MessageCollection
```

**MarkMessagesAsReadAction**
```php
public function handle(
    Model $user,
    int $conversationId,
    array $messageIds = []
): void
```

**DeleteMessageAction**
```php
public function handle(Model $user, int $messageId): void
```

### Utility Actions

**GetAvatarAction**
```php
public function handle(?Model $user): ?string
```

**GetOnlineUsersAction**
```php
public function handle(int $conversationId): array
```

## Creating Custom Actions

### Basic Structure

```php
<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Akira\LaravelChat\Config\ChatConfig;
use Illuminate\Database\Eloquent\Model;

final readonly class MyCustomAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    public function handle(/* parameters */): mixed
    {
        // Your business logic here
    }
}
```

### Example: Archive Conversation

```php
<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ArchiveConversationAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    public function handle(Model $user, int $conversationId): void
    {
        $conversationModel = $this->config->getConversationModel();
        
        /** @var Conversation|null $conversation */
        $conversation = $conversationModel::query()
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->find($conversationId);

        if (!$conversation) {
            throw new ModelNotFoundException('Conversation not found');
        }

        // Archive the conversation
        $conversation->participants()
            ->wherePivot('user_id', $user->id)
            ->updateExistingPivot($user->id, [
                'archived_at' => now(),
            ]);
    }
}
```

### Example: Pin Message

```php
<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Message;
use Illuminate\Database\Eloquent\Model;
use UnauthorizedHttpException;

final readonly class PinMessageAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    public function handle(Model $user, int $messageId): Message
    {
        $messageModel = $this->config->getMessageModel();
        
        /** @var Message $message */
        $message = $messageModel::query()
            ->with('conversation.participants')
            ->findOrFail($messageId);

        // Verify user is admin in the conversation
        $isAdmin = $message->conversation->participants()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('is_admin', true)
            ->exists();

        if (!$isAdmin) {
            throw new UnauthorizedHttpException('Only admins can pin messages');
        }

        $message->update(['pinned_at' => now()]);

        return $message->fresh();
    }
}
```

## Action Best Practices

### 1. Use Type Declarations

```php
// ✅ Good
public function handle(Model $user, int $id): MessageCollection

// ❌ Avoid
public function handle($user, $id)
```

### 2. Make Actions Readonly

```php
// ✅ Good
final readonly class MyAction

// ❌ Avoid
class MyAction
```

### 3. Inject Dependencies

```php
// ✅ Good
public function __construct(private ChatConfig $config) {}

// ❌ Avoid
public function handle()
{
    $config = config('chat'); // Direct access
}
```

### 4. Return Value Objects

```php
// ✅ Good
public function handle(): ConversationCollection

// ❌ Avoid
public function handle(): array
```

### 5. Single Responsibility

```php
// ✅ Good - One action, one purpose
class SendMessageAction { }
class DeleteMessageAction { }

// ❌ Avoid - Multiple responsibilities
class MessageAction
{
    public function send() { }
    public function delete() { }
    public function edit() { }
}
```

## Integration with Managers

Actions are orchestrated by Manager classes:

```php
class MessageManager
{
    public function __construct(
        private SendMessageAction $sendAction,
        private GetConversationMessagesAction $getMessagesAction,
        private MarkMessagesAsReadAction $markAsReadAction,
        private DeleteMessageAction $deleteAction,
    ) {}

    public function send(Model $user, int $conversationId, string $content): Message
    {
        return $this->sendAction->handle($user, $conversationId, $content);
    }

    public function getConversationMessages(Model $user, int $conversationId): MessageCollection
    {
        return $this->getMessagesAction->handle($user, $conversationId);
    }
}
```

## Testing Actions

### Unit Testing

```php
use Akira\LaravelChat\Actions\SendMessageAction;
use Akira\LaravelChat\Models\Message;

test('sends message to conversation', function () {
    $user = User::factory()->create();
    $conversation = createConversation($user);
    
    $action = app(SendMessageAction::class);
    $message = $action->handle($user, $conversation->id, 'Hello World');
    
    expect($message)
        ->toBeInstanceOf(Message::class)
        ->and($message->content)->toBe('Hello World')
        ->and($message->user_id)->toBe($user->id)
        ->and($message->conversation_id)->toBe($conversation->id);
});
```

### Testing with Mocks

```php
test('validates user is participant before sending', function () {
    $user = User::factory()->create();
    $conversation = createConversation(); // User not included
    
    $action = app(SendMessageAction::class);
    
    expect(fn() => $action->handle($user, $conversation->id, 'Hello'))
        ->toThrow(ModelNotFoundException::class);
});
```

## Advanced Patterns

### Transactional Actions

```php
use Illuminate\Support\Facades\DB;

public function handle(Model $user, array $data): CreateConversationResult
{
    return DB::transaction(function () use ($user, $data) {
        // Multiple database operations
        $conversation = $this->createConversation($data);
        $this->attachParticipants($conversation, $data['participants']);
        $this->sendWelcomeMessage($conversation, $user);
        
        return new CreateConversationResult(/*...*/);
    });
}
```

### Event Dispatching

```php
public function handle(Model $user, int $conversationId, string $content): Message
{
    $message = $conversation->messages()->create([
        'user_id' => $user->id,
        'content' => $content,
    ]);

    // Dispatch event after creation
    if ($this->config->isBroadcastingEnabled()) {
        MessageSent::dispatch($message);
    }

    return $message;
}
```

### Validation

```php
public function handle(Model $creator, string $type, array $participantIds): CreateConversationResult
{
    // Validate conversation type
    if (!$this->config->isValidConversationType($type)) {
        throw new InvalidArgumentException("Invalid conversation type: {$type}");
    }

    // Validate participant count
    if ($type === 'direct' && count($participantIds) !== 1) {
        throw new InvalidArgumentException('Direct conversations must have exactly one other participant');
    }

    // Create conversation
}
```

## Conclusion

The Action Pattern provides:

- **Clarity**: Each action has one clear purpose
- **Reusability**: Use actions anywhere in your application
- **Testability**: Easy to test in isolation
- **Maintainability**: Changes are localized
- **Composability**: Combine actions to create complex workflows

By using actions, the Laravel Chat Package maintains clean, maintainable, and testable code that follows SOLID principles.


---

## Navigation

← [Previous: solid.md](solid.md) | [Index](INDEX.md) | [Next: advanced.md](advanced.md) →

