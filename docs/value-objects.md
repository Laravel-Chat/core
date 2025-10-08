# Value Objects

The Laravel Chat Package uses Value Objects to encapsulate response data, providing a flexible and type-safe way to work with chat data. Value Objects follow Laravel conventions and can be easily converted to arrays, collections, or JSON.

## Overview

Value Objects provide:
- **Type Safety** - Strongly typed properties for better IDE support
- **Flexibility** - Convert to arrays, collections, or JSON as needed
- **Immutability** - Read-only objects prevent accidental mutations
- **Laravel Integration** - Implements Laravel's Arrayable and Jsonable interfaces
- **Chainable Methods** - Fluent API for filtering and transforming data

## Available Value Objects

### ConversationResult

Represents a single conversation with all its details.

```php
use Akira\LaravelChat\Support\ValueObjects\ConversationResult;

$conversation = ConversationResult {
    +id: 1
    +type: "direct"
    +title: "John Doe"
    +createdBy: 1
    +participants: [...]
    +lastMessage: [...]
    +lastMessageAt: "2024-01-15T10:30:00+00:00"
    +unreadCount: 3
    +avatarUrl: "https://..."
    +otherParticipant: User {...}
}
```

**Methods:**

```php
// Convert to array
$array = $conversation->toArray();

// Convert to collection
$collection = $conversation->toCollection();

// Convert to JSON
$json = $conversation->toJson();

// Get minimal array (without model instances)
$minimalArray = $conversation->toMinimalArray();
```

### ConversationCollection

A collection of conversations with additional utility methods.

```php
use Akira\LaravelChat\Facades\Conversation;

$conversations = Conversation::getUserConversations($user);
// Returns ConversationCollection

// Get the count
$count = $conversations->count();

// Check if empty
if ($conversations->isEmpty()) {
    // No conversations
}

// Filter by type
$directChats = $conversations->filterByType('direct');
$groupChats = $conversations->filterByType('group');

// Convert to array
$array = $conversations->toArray();
// Returns: ['conversations' => [...], 'total' => 10]

// Get minimal representation
$minimal = $conversations->toMinimalArray();

// Get underlying collection
$collection = $conversations->getConversations();
```

### MessageResult

Represents a single message with all its data.

```php
use Akira\LaravelChat\Support\ValueObjects\MessageResult;

$message = MessageResult {
    +id: 123
    +conversationId: 1
    +userId: 2
    +content: "Hello!"
    +type: "text"
    +metadata: null
    +readAt: "2024-01-15T10:35:00+00:00"
    +createdAt: "2024-01-15T10:30:00+00:00"
    +user: [...]
}
```

**Methods:**

```php
// Check if message is read
if ($message->isRead()) {
    // Message has been read
}

// Check message type
if ($message->isType('text')) {
    // Handle text message
}

// Convert to array
$array = $message->toArray();

// Convert to JSON
$json = $message->toJson();
```

### MessageCollection

A collection of messages with filtering and utility methods.

```php
use Akira\LaravelChat\Facades\Message;

$messages = Message::getConversationMessages($user, $conversationId);
// Returns MessageCollection

// Get the count
$count = $messages->count();

// Check if empty
if ($messages->isEmpty()) {
    // No messages
}

// Get unread messages
$unread = $messages->getUnread();

// Filter by type
$textMessages = $messages->filterByType('text');
$imageMessages = $messages->filterByType('image');
$fileMessages = $messages->filterByType('file');

// Filter by user
$myMessages = $messages->filterByUser($user->id);

// Convert to array
$array = $messages->toArray();
// Returns: ['messages' => [...], 'total' => 50, 'conversation_id' => 1]

// Get underlying collection
$collection = $messages->getMessages();
```

### CreateConversationResult

Response when creating a conversation.

```php
use Akira\LaravelChat\Facades\Conversation;

$result = Conversation::create($user, 'direct', [$recipientId]);
// Returns CreateConversationResult

// Access properties
$conversationId = $result->id;
$message = $result->message;
$wasExisting = $result->existing;

// Helper methods
if ($result->wasExisting()) {
    // Conversation already existed
}

if ($result->wasCreated()) {
    // New conversation was created
}

// Convert to array
$array = $result->toArray();
// Returns: ['id' => 1, 'message' => '...', 'existing' => false]
```

## Usage Examples

### Working with Conversations

```php
use Akira\LaravelChat\Facades\Conversation;

// Get all conversations
$conversations = Conversation::getUserConversations($user);

// Convert to array for API response
return response()->json($conversations->toArray());

// Convert to collection for processing
$conversations->toCollection()->each(function ($conversation) {
    // Process each conversation
    logger($conversation->title);
});

// Filter and transform
$directChats = $conversations
    ->filterByType('direct')
    ->map(function ($chat) {
        return [
            'id' => $chat->id,
            'name' => $chat->title,
            'unread' => $chat->unreadCount,
        ];
    });
```

### Working with Messages

```php
use Akira\LaravelChat\Facades\Message;

// Get messages for a conversation
$messages = Message::getConversationMessages($user, $conversationId);

// Get only unread messages
$unreadMessages = $messages->getUnread();

// Get messages from a specific user
$theirMessages = $messages->filterByUser($otherUserId);

// Get only text messages
$textOnly = $messages->filterByType('text');

// Return as API response
return response()->json([
    'data' => $messages->toArray(),
    'unread_count' => $messages->getUnread()->count(),
]);

// Process messages
$messages->getMessages()->each(function ($message) {
    if ($message->isType('file')) {
        // Handle file message
        $fileUrl = $message->metadata['file_url'] ?? null;
    }
});
```

### Creating Conversations

```php
use Akira\LaravelChat\Facades\Conversation;

// Create or get existing conversation
$result = Conversation::create($user, 'direct', [$recipientId]);

if ($result->wasCreated()) {
    // Send welcome message
    Message::send($user, $result->id, 'Hello!');
} else {
    // Conversation already exists
    return redirect()->route('chat.show', $result->id);
}

// Return API response
return response()->json($result->toArray());
```

### Advanced Filtering

```php
// Chain multiple filters
$recentUnreadMessages = Message::getConversationMessages($user, $conversationId)
    ->getUnread()
    ->filter(function ($message) {
        return $message->createdAt > now()->subHours(24);
    });

// Group messages by type
$messagesByType = Message::getConversationMessages($user, $conversationId)
    ->getMessages()
    ->groupBy('type');

// Get statistics
$stats = [
    'total' => $messages->count(),
    'unread' => $messages->getUnread()->count(),
    'text' => $messages->filterByType('text')->count(),
    'files' => $messages->filterByType('file')->count(),
];
```

## Converting Between Formats

### To Array

```php
// Conversation to array
$array = $conversation->toArray();

// Collection to array
$array = $conversations->toArray();

// Results in nested array structure
[
    'conversations' => [
        ['id' => 1, 'title' => '...', ...],
        ['id' => 2, 'title' => '...', ...],
    ],
    'total' => 2
]
```

### To Collection

```php
// Single value object to collection
$collection = $conversation->toCollection();

// Collection value object's underlying collection
$collection = $conversations->getConversations();

// Now you can use all Laravel collection methods
$filtered = $collection->filter(function ($conv) {
    return $conv->unreadCount > 0;
});
```

### To JSON

```php
// Direct JSON conversion
$json = $conversation->toJson();

// Laravel API resource-style
return response()->json($conversation);

// With custom options
$json = $conversation->toJson(JSON_PRETTY_PRINT);
```

## Best Practices

### 1. Use Appropriate Return Types

```php
// ✅ Good - Return value objects from controllers
public function index(Request $request)
{
    $conversations = Conversation::getUserConversations($request->user());
    
    return response()->json($conversations);
}

// ❌ Avoid - Converting to array prematurely
public function index(Request $request)
{
    $conversations = Conversation::getUserConversations($request->user());
    $array = $conversations->toArray(); // Loses type safety
    
    return response()->json($array);
}
```

### 2. Leverage Type Safety

```php
// ✅ Good - IDE autocomplete and type checking
$conversations = Conversation::getUserConversations($user);
$count = $conversations->count(); // IDE knows this method exists

// ✅ Good - Type-safe property access
foreach ($conversations->getConversations() as $conversation) {
    echo $conversation->title; // IDE autocompletes
}
```

### 3. Filter Before Converting

```php
// ✅ Good - Filter first, convert last
$directChats = Conversation::getUserConversations($user)
    ->filterByType('direct')
    ->toArray();

// ❌ Avoid - Converting then filtering loses methods
$array = Conversation::getUserConversations($user)->toArray();
// Can't use filterByType() anymore
```

### 4. Use Collections for Complex Operations

```php
// ✅ Good - Get collection for complex operations
$collection = $conversations->getConversations();

$grouped = $collection->groupBy('type');
$sorted = $collection->sortByDesc('lastMessageAt');
$mapped = $collection->map(fn($c) => $c->toMinimalArray());
```

## Integration with Laravel

### API Resources

```php
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        // Value objects work seamlessly
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'unread_count' => $this->unreadCount,
            'participants' => $this->participants,
        ];
    }
}
```

### Eloquent Collections

```php
// Value objects integrate with Eloquent collections
$conversations = Conversation::getUserConversations($user);

// Use collection methods
$conversations->getConversations()
    ->each(function ($conversation) {
        Cache::put("conv_{$conversation->id}", $conversation->toArray());
    });
```

### Testing

```php
public function test_user_can_get_conversations()
{
    $user = User::factory()->create();
    
    $conversations = Conversation::getUserConversations($user);
    
    $this->assertInstanceOf(ConversationCollection::class, $conversations);
    $this->assertEquals(0, $conversations->count());
}
```

## Value Object Architecture

### Interface-Based Design (SOLID Principles)

The package follows SOLID principles by using contracts (interfaces) instead of inheritance. Each value object implements specific contracts for its capabilities:

```php
// Core contract - all value objects implement this
interface ValueObjectContract extends Arrayable, Collectable, Jsonable
{
    // Combines all conversion capabilities
}

// Individual capability contracts
interface Arrayable
{
    public function toArray(): array;
}

interface Collectable
{
    public function toCollection(): Collection;
}

interface Jsonable
{
    public function toJson(int $options = 0): string;
}
```

### No Inheritance Required

Value objects implement contracts directly, not through inheritance:

```php
// ✅ Good - Implements contracts
class ConversationResult implements ValueObjectContract, JsonSerializable
{
    // Full implementation control
}

// ❌ Old approach - inheritance (not used)
class ConversationResult extends AbstractValueObject
{
    // Less flexible
}
```

This approach follows the **Interface Segregation Principle** and **Dependency Inversion Principle**.

---

## Custom Value Objects

Create custom value objects by implementing the required interfaces:

```php
namespace App\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

class CustomChatStats implements ValueObjectContract, JsonSerializable
{
    public function __construct(
        public readonly int $totalMessages,
        public readonly int $totalConversations,
        public readonly array $topUsers,
    ) {}
    
    public function toArray(): array
    {
        return [
            'total_messages' => $this->totalMessages,
            'total_conversations' => $this->totalConversations,
            'top_users' => $this->topUsers,
        ];
    }
    
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }
    
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options) ?: '{}';
    }
    
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
```

### Why Contracts Over Inheritance?

1. **Flexibility** - Implement only what you need
2. **No fragile base class** - No unexpected parent class changes
3. **Multiple behaviors** - Can implement multiple contracts
4. **Testability** - Easy to mock contracts
5. **SOLID compliance** - Follows interface segregation principle

### Minimal Implementation

If you only need array conversion:

```php
use Akira\LaravelChat\Support\Contracts\Arrayable;

class SimpleResult implements Arrayable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}
    
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
```


---

## Navigation

← [Previous: facades.md](facades.md) | [Index](INDEX.md) | [Next: policies.md](policies.md) →


