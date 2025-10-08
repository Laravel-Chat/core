# Testing Guide

Learn how to test your chat implementation with Pest PHP.

## Table of Contents

- [Setup](#setup)
- [Testing Conversations](#testing-conversations)
- [Testing Messages](#testing-messages)
- [Testing Policies](#testing-policies)
- [Testing Broadcasting](#testing-broadcasting)
- [Testing Custom Actions](#testing-custom-actions)
- [Testing Value Objects](#testing-value-objects)
- [Mocking and Fakes](#mocking-and-fakes)
- [Best Practices](#best-practices)

## Setup

The package uses Pest PHP for testing. Ensure your test environment is configured:

```php
// tests/Pest.php
uses(Tests\TestCase::class)
    ->in('Feature', 'Unit');

uses(
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');
```

---

## Testing Conversations

### Test Creating Direct Conversation

```php
use Akira\LaravelChat\Facades\Conversation;
use App\Models\User;

test('user can create direct conversation', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $result = Conversation::create($user, 'direct', [$recipient->id]);

    expect($result)
        ->toBeInstanceOf(\Akira\LaravelChat\Support\ValueObjects\CreateConversationResult::class)
        ->id->toBeInt()
        ->wasCreated()->toBeTrue();

    $this->assertDatabaseHas('conversations', [
        'id' => $result->id,
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $this->assertDatabaseCount('conversation_participants', 2);
});
```

### Test Creating Group Conversation

```php
test('user can create group conversation', function () {
    $creator = User::factory()->create();
    $users = User::factory()->count(3)->create();

    $result = Conversation::create(
        $creator, 
        'group', 
        $users->pluck('id')->toArray(),
        'Team Discussion'
    );

    expect($result)
        ->id->toBeInt()
        ->existing->toBeFalse();

    $this->assertDatabaseHas('conversations', [
        'id' => $result->id,
        'type' => 'group',
        'title' => 'Team Discussion',
    ]);

    $this->assertDatabaseCount('conversation_participants', 4); // 3 + creator
});
```

### Test Finding Existing Conversation

```php
test('returns existing direct conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // First creation
    $result1 = Conversation::create($user1, 'direct', [$user2->id]);
    
    // Second attempt
    $result2 = Conversation::create($user1, 'direct', [$user2->id]);

    expect($result1->id)->toBe($result2->id);
    expect($result2->existing)->toBeTrue();
    
    $this->assertDatabaseCount('conversations', 1);
});
```

### Test Getting User Conversations

```php
test('user can get all conversations', function () {
    $user = User::factory()->create();
    $others = User::factory()->count(3)->create();

    // Create conversations
    foreach ($others as $other) {
        Conversation::create($user, 'direct', [$other->id]);
    }

    $conversations = Conversation::getUserConversations($user);

    expect($conversations)
        ->toBeInstanceOf(\Akira\LaravelChat\Support\ValueObjects\ConversationCollection::class)
        ->count()->toBe(3);
});
```

### Test Deleting Conversation

```php
test('user can delete conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $result = Conversation::create($user, 'direct', [$other->id]);
    
    Conversation::delete($result->id, $user);

    $this->assertDatabaseMissing('conversations', ['id' => $result->id]);
});
```

### Test Unauthorized Deletion

```php
test('non-participant cannot delete conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $outsider = User::factory()->create();

    $result = Conversation::create($user, 'direct', [$other->id]);
    
    expect(fn() => Conversation::delete($result->id, $outsider))
        ->toThrow(Exception::class);
});
```

---

## Testing Messages

### Test Sending Message

```php
use Akira\LaravelChat\Facades\Message;

test('user can send message', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    
    $message = Message::send($user, $conversation->id, 'Hello!');

    expect($message)
        ->toBeInstanceOf(\Akira\LaravelChat\Models\Message::class)
        ->content->toBe('Hello!')
        ->type->toBe('text');

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Hello!',
    ]);
});
```

### Test Sending Message with Metadata

```php
test('user can send message with metadata', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    
    $message = Message::send($user, $conversation->id, 'Check this image', 'image', [
        'image_url' => 'https://example.com/image.jpg',
        'width' => 1920,
        'height' => 1080,
    ]);

    expect($message)
        ->type->toBe('image')
        ->metadata->toBe([
            'image_url' => 'https://example.com/image.jpg',
            'width' => 1920,
            'height' => 1080,
        ]);
});
```

### Test Getting Conversation Messages

```php
test('user can get conversation messages', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    
    // Send messages
    Message::send($user, $conversation->id, 'Message 1');
    Message::send($recipient, $conversation->id, 'Message 2');
    Message::send($user, $conversation->id, 'Message 3');

    $messages = Message::getConversationMessages($user, $conversation->id);

    expect($messages)
        ->toBeInstanceOf(\Akira\LaravelChat\Support\ValueObjects\MessageCollection::class)
        ->count()->toBe(3);
});
```

### Test Marking Messages as Read

```php
test('user can mark messages as read', function () {
    $user = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$sender->id]);
    
    // Sender sends messages
    $msg1 = Message::send($sender, $conversation->id, 'Message 1');
    $msg2 = Message::send($sender, $conversation->id, 'Message 2');

    // Recipient marks as read
    Message::markAsRead($user, $conversation->id);

    $this->assertDatabaseHas('messages', [
        'id' => $msg1->id,
        'read_at' => now(),
    ]);
    
    $this->assertDatabaseHas('messages', [
        'id' => $msg2->id,
        'read_at' => now(),
    ]);
});
```

### Test Deleting Message

```php
test('user can delete own message', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    $message = Message::send($user, $conversation->id, 'Test');

    Message::delete($message->id, $user);

    $this->assertDatabaseMissing('messages', ['id' => $message->id]);
});
```

### Test Unauthorized Message Deletion

```php
test('user cannot delete others messages', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$other->id]);
    $message = Message::send($other, $conversation->id, 'Test');

    expect(fn() => Message::delete($message->id, $user))
        ->toThrow(Exception::class);
});
```

---

## Testing Policies

### Test Default Policy

```php
test('default policy allows all messages', function () {
    config(['chat.policies.message_policy' => \Akira\LaravelChat\Policies\DefaultMessagePolicy::class]);

    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $result = Conversation::create($user, 'direct', [$recipient->id]);

    expect($result)->id->toBeInt();
});
```

### Test Custom Policy

```php
test('custom policy blocks messages', function () {
    // Custom policy that blocks all
    $policy = new class implements \Akira\LaravelChat\Policies\MessagePolicyContract {
        public function canSendMessage($sender, $recipient): bool
        {
            return false;
        }

        public function canReceiveMessage($recipient, $sender): bool
        {
            return false;
        }
    };

    config(['chat.policies.message_policy' => get_class($policy)]);
    app()->instance(get_class($policy), $policy);

    $user = User::factory()->create();
    $recipient = User::factory()->create();

    expect(fn() => Conversation::create($user, 'direct', [$recipient->id]))
        ->toThrow(Exception::class);
});
```

### Test Blocking Policy

```php
test('blocked user cannot send messages', function () {
    $user = User::factory()->create();
    $blocked = User::factory()->create();

    // Assume User model has blockUser method
    $user->blockUser($blocked);

    config(['chat.policies.message_policy' => \App\Policies\BlockingPolicy::class]);

    expect(fn() => Conversation::create($blocked, 'direct', [$user->id]))
        ->toThrow(Exception::class, 'does not accept messages');
});
```

---

## Testing Broadcasting

### Test Event is Dispatched

```php
use Akira\LaravelChat\Events\MessageSent;
use Illuminate\Support\Facades\Event;

test('message sent event is dispatched', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    Message::send($user, $conversation->id, 'Hello!');

    Event::assertDispatched(MessageSent::class);
});
```

### Test Event Data

```php
test('message sent event contains correct data', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    $message = Message::send($user, $conversation->id, 'Hello!');

    Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
        return $event->message->id === $message->id &&
               $event->message->content === 'Hello!';
    });
});
```

### Test Broadcasting Channels

```php
use Illuminate\Support\Facades\Broadcast;

test('user can access own channel', function () {
    $user = User::factory()->create();

    $result = Broadcast::channel('chat.user.'.$user->id, function ($authUser) use ($user) {
        return (int) $authUser->id === (int) $user->id;
    });

    expect($result)->toBeCallable();
});
```

---

## Testing Custom Actions

### Test Custom Action

```php
use App\Actions\Chat\PinMessageAction;

test('user can pin message', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    $message = Message::send($user, $conversation->id, 'Important!');

    $action = app(PinMessageAction::class);
    $pinnedMessage = $action->handle($user, $message->id);

    expect($pinnedMessage->is_pinned)->toBeTrue();
    
    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'is_pinned' => true,
    ]);
});
```

---

## Testing Value Objects

### Test Value Object Creation

```php
test('conversation result can be created', function () {
    $result = new \Akira\LaravelChat\Support\ValueObjects\ConversationResult(
        id: 1,
        type: 'direct',
        title: 'Chat',
        createdBy: 1,
        participants: [],
        lastMessage: null,
        lastMessageAt: null,
        unreadCount: 0,
    );

    expect($result)
        ->id->toBe(1)
        ->type->toBe('direct')
        ->unreadCount->toBe(0);
});
```

### Test Value Object Conversion

```php
test('conversation result converts to array', function () {
    $result = new \Akira\LaravelChat\Support\ValueObjects\ConversationResult(
        id: 1,
        type: 'direct',
        title: 'Chat',
        createdBy: 1,
        participants: [],
        lastMessage: null,
        lastMessageAt: null,
        unreadCount: 5,
    );

    $array = $result->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKey('id', 1)
        ->toHaveKey('unread_count', 5);
});
```

### Test Collection Methods

```php
test('conversation collection filters by type', function () {
    $user = User::factory()->create();
    $users = User::factory()->count(3)->create();

    // Create direct conversations
    foreach ($users->take(2) as $other) {
        Conversation::create($user, 'direct', [$other->id]);
    }

    // Create group conversation
    Conversation::create($user, 'group', $users->pluck('id')->toArray(), 'Team');

    $conversations = Conversation::getUserConversations($user);
    $directChats = $conversations->filterByType('direct');
    $groupChats = $conversations->filterByType('group');

    expect($directChats->count())->toBe(2);
    expect($groupChats->count())->toBe(1);
});
```

---

## Mocking and Fakes

### Mock ChatConfig

```php
use Akira\LaravelChat\Config\ChatConfig;

test('action works with mocked config', function () {
    $mockConfig = Mockery::mock(ChatConfig::class);
    $mockConfig->shouldReceive('getConversationModel')
        ->andReturn(\Akira\LaravelChat\Models\Conversation::class);

    app()->instance(ChatConfig::class, $mockConfig);

    // Your test code
});
```

### Fake Events

```php
use Illuminate\Support\Facades\Event;

test('message sent without broadcasting', function () {
    Event::fake();

    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    Message::send($user, $conversation->id, 'Test');

    // Events are faked, no actual broadcasting
    Event::assertDispatched(\Akira\LaravelChat\Events\MessageSent::class);
});
```

---

## Best Practices

### 1. Use Factories

```php
// ✅ Good - Reusable factories
$users = User::factory()->count(5)->create();

// ❌ Bad - Manual creation
$user1 = User::create(['name' => 'User 1', ...]);
$user2 = User::create(['name' => 'User 2', ...]);
```

### 2. Test Isolation

```php
// ✅ Good - Each test is independent
test('user can create conversation', function () {
    $user = User::factory()->create();
    // Test code
});

test('user can delete conversation', function () {
    $user = User::factory()->create(); // Fresh user
    // Test code
});
```

### 3. Descriptive Test Names

```php
// ✅ Good - Clear intent
test('blocked user cannot send messages to blocker')

// ❌ Bad - Unclear
test('test messages')
```

### 4. Arrange-Act-Assert Pattern

```php
test('user can send message', function () {
    // Arrange
    $user = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = Conversation::create($user, 'direct', [$recipient->id]);
    
    // Act
    $message = Message::send($user, $conversation->id, 'Hello!');
    
    // Assert
    expect($message)->content->toBe('Hello!');
    $this->assertDatabaseHas('messages', ['id' => $message->id]);
});
```

### 5. Test Edge Cases

```php
test('cannot create direct conversation with self', function () {
    $user = User::factory()->create();

    expect(fn() => Conversation::create($user, 'direct', [$user->id]))
        ->toThrow(Exception::class);
});

test('group conversation requires title', function () {
    $user = User::factory()->create();
    $users = User::factory()->count(2)->create();

    expect(fn() => Conversation::create($user, 'group', $users->pluck('id')->toArray()))
        ->toThrow(Exception::class);
});
```

---

## Navigation

← [Previous: advanced.md](advanced.md) | [Index](INDEX.md) | [Next: api-reference.md](api-reference.md) →
