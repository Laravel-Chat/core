# Architecture Overview

This document provides a comprehensive overview of the Laravel Chat Package architecture, design patterns, and organizational principles.

## Package Structure

```
laravel-chat/
├── src/
│   ├── Actions/              # Single-purpose action classes
│   ├── Config/               # Configuration management
│   ├── Database/            
│   │   └── Migrations/       # Database migrations
│   ├── Events/               # Broadcast events
│   ├── Facades/              # Laravel facades
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Permission policies
│   ├── Support/
│   │   ├── Contracts/        # Contracts (interfaces)
│   │   ├── ValueObjects/     # Value object responses
│   │   ├── ConversationManager.php
│   │   └── MessageManager.php
│   ├── Traits/               # Reusable traits
│   └── ChatServiceProvider.php
├── tests/                    # Pest tests
├── config/                   # Configuration files
└── docs/                     # Documentation
```

## Design Patterns

### 1. Action Pattern

The package uses the **Action Pattern** to encapsulate business logic into single-purpose, reusable classes.

**Benefits:**
- Single Responsibility Principle
- Easy to test in isolation
- Reusable across different contexts
- Clear separation of concerns

**Example:**
```php
// Each action has one clear purpose
CreateConversationAction::handle()
SendMessageAction::handle()
GetConversationsAction::handle()
```

[Learn more about Actions →](actions.md)

### 2. Facade Pattern

Laravel Facades provide a static interface to underlying services.

```php
// Clean, expressive API
Conversation::create($user, 'direct', [$recipientId]);
Message::send($user, $conversationId, 'Hello!');
```

**Structure:**
```
Facade → Manager → Action → Model
```

### 3. Singleton Pattern

The configuration class uses the Singleton pattern to ensure a single instance throughout the application lifecycle.

```php
ChatConfig::getInstance();
```

**Benefits:**
- Consistent configuration across the app
- Performance optimization
- Memory efficiency

### 4. Value Object Pattern

Responses are encapsulated in Value Objects for type safety and flexibility.

```php
ConversationCollection  // Collection of conversations
MessageCollection       // Collection of messages
CreateConversationResult // Creation result
```

[Learn more about Value Objects →](value-objects.md)

### 5. Policy Pattern

The package implements the Strategy pattern through configurable policies.

```php
interface MessagePolicyContract {
    public function canSendMessage(Model $sender, Model $recipient): bool;
    public function canReceiveMessage(Model $recipient, Model $sender): bool;
}
```

[Learn more about Policies →](policies.md)

### 6. Observer Pattern (Events)

Laravel's event system enables loose coupling and extensibility.

```php
// Events dispatched automatically
ConversationCreated::dispatch($conversation, $participant);
MessageSent::dispatch($message);
MessageReceived::dispatch($message, $recipient);
```

## Layer Architecture

### 1. Presentation Layer (Facades)

**Purpose:** Provide a clean, user-friendly API

```php
// Facades/Conversation.php
class Conversation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConversationManager::class;
    }
}
```

### 2. Application Layer (Managers)

**Purpose:** Orchestrate actions and coordinate workflow

```php
// Support/ConversationManager.php
class ConversationManager
{
    public function __construct(
        private CreateConversationAction $createAction,
        private GetConversationsAction $getAction,
        // ...
    ) {}
}
```

### 3. Domain Layer (Actions)

**Purpose:** Implement business logic

```php
// Actions/CreateConversationAction.php
class CreateConversationAction
{
    public function handle(Model $creator, string $type, array $participantIds)
    {
        // Business logic here
    }
}
```

### 4. Data Layer (Models)

**Purpose:** Represent and persist data

```php
// Models/Conversation.php
class Conversation extends Model
{
    public function messages() { }
    public function participants() { }
}
```

## Request Flow

### Creating a Conversation

```
1. Controller/Service
   ↓
2. Facade: Conversation::create()
   ↓
3. Manager: ConversationManager->create()
   ↓
4. Action: CreateConversationAction->handle()
   ↓
5. Model: Conversation::create()
   ↓
6. Event: ConversationCreated::dispatch()
   ↓
7. Return: CreateConversationResult
```

### Sending a Message

```
1. Controller/Service
   ↓
2. Facade: Message::send()
   ↓
3. Manager: MessageManager->send()
   ↓
4. Action: SendMessageAction->handle()
   ↓
5. Validation: Check user is participant
   ↓
6. Model: Message::create()
   ↓
7. Event: MessageSent::dispatch()
   ↓
8. Broadcast: Real-time notification
   ↓
9. Return: Message model
```

## Dependency Injection

The package uses Laravel's Service Container for dependency injection.

### Service Provider Registration

```php
class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton configuration
        $this->app->singleton(ChatConfig::class, function () {
            return ChatConfig::getInstance();
        });

        // Manager bindings
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(MessageManager::class);
    }
}
```

### Constructor Injection

```php
class CreateConversationAction
{
    public function __construct(
        private ChatConfig $config,
        private FindDirectConversationAction $findDirectAction
    ) {}
}
```

## Configuration Management

### Centralized Configuration

All configuration is managed through the `ChatConfig` singleton:

```php
class ChatConfig
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = self::fromConfig();
        }
        return self::$instance;
    }
}
```

### Configuration Access

```php
$config = ChatConfig::getInstance();

$userModel = $config->getUserModel();
$enabled = $config->isBroadcastingEnabled();
$policy = $config->getMessagePolicy();
```

## Database Design

### Tables

**conversations**
- id
- type (direct/group)
- title
- created_by
- last_message_at
- timestamps

**messages**
- id
- conversation_id
- user_id
- content
- type
- metadata (JSON)
- read_at
- timestamps

**conversation_participants**
- conversation_id
- user_id
- joined_at
- last_read_at
- is_admin
- timestamps

### Relationships

```
User
  ├── has many messages
  └── belongs to many conversations

Conversation
  ├── has many messages
  ├── belongs to many participants (users)
  └── belongs to creator (user)

Message
  ├── belongs to conversation
  └── belongs to user
```

## Extension Points

### 1. Custom Models

Extend the default models:

```php
class CustomConversation extends Conversation
{
    // Add custom methods
}

// config/chat.php
'models' => [
    'conversation' => CustomConversation::class,
]
```

### 2. Custom Policies

Implement your own permission logic:

```php
class CustomMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Custom logic
    }
}
```

### 3. Custom Actions

Create additional actions:

```php
class ArchiveConversationAction
{
    public function handle(Model $user, int $conversationId): void
    {
        // Archive logic
    }
}
```

### 4. Event Listeners

Listen to package events:

```php
Event::listen(MessageSent::class, function ($event) {
    // Send notification
    // Update statistics
    // Log activity
});
```

## Performance Considerations

### Query Optimization

**Eager Loading:**
```php
$conversations = $user->conversations()
    ->with('participants')
    ->with(['messages' => fn($q) => $q->latest()->limit(1)])
    ->get();
```

**Indexes:**
- Foreign keys are indexed
- Composite indexes on frequently queried columns
- Index on `last_message_at` for sorting

### Caching Strategy

```php
// Cache user conversations
$conversations = Cache::remember(
    "user.{$user->id}.conversations",
    now()->addMinutes(10),
    fn() => Conversation::getUserConversations($user)
);
```

### Broadcasting

Use Laravel Echo with Reverb for efficient real-time updates:

```php
// Only broadcast when enabled
if ($this->config->isBroadcastingEnabled()) {
    MessageSent::dispatch($message);
}
```

## Testing Architecture

### Test Structure

```
tests/
├── Unit/
│   ├── Actions/         # Test each action
│   ├── Models/          # Test model relationships
│   ├── Policies/        # Test permission logic
│   └── Config/          # Test configuration
└── Feature/
    ├── ConversationFacadeTest.php
    └── MessageFacadeTest.php
```

### Testing Principles

- **Unit Tests**: Test actions and models in isolation
- **Feature Tests**: Test through facades (user perspective)
- **Database Tests**: Use in-memory SQLite for speed
- **Coverage**: Critical paths have 100% coverage

[Learn more about Testing →](testing.md)

## Security Considerations

### Authentication

All operations require authenticated users:

```php
// Check user is participant
$conversation = Conversation::query()
    ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
    ->findOrFail($conversationId);
```

### Authorization

Policy-based permissions:

```php
// Check if user can receive messages
if (!$recipient->canReceiveMessagesFrom($sender)) {
    throw new Exception('User does not accept messages');
}
```

### Input Validation

```php
// Validate conversation type
if (!$config->isValidConversationType($type)) {
    throw new Exception('Invalid conversation type');
}
```

### SQL Injection Prevention

- All queries use Eloquent ORM
- Parameter binding automatically applied
- No raw SQL queries without bindings

## Best Practices

### 1. Use Dependency Injection

```php
// ✅ Good
class MyAction
{
    public function __construct(private ChatConfig $config) {}
}

// ❌ Avoid
class MyAction
{
    public function handle()
    {
        $config = config('chat'); // Direct access
    }
}
```

### 2. Type Declarations

```php
// ✅ Good
public function handle(Model $user, int $id): MessageCollection

// ❌ Avoid
public function handle($user, $id)
```

### 3. Immutability

```php
// ✅ Good - Readonly classes
readonly class ConversationManager

// ✅ Good - Readonly properties
public readonly int $id
```

### 4. Return Value Objects

```php
// ✅ Good
public function handle(): ConversationCollection

// ❌ Avoid
public function handle(): array
```

## Roadmap

Future architectural improvements:

- [ ] Queue support for async operations
- [ ] Rate limiting for message sending
- [ ] Message search functionality
- [ ] File upload handling
- [ ] Message reactions
- [ ] Typing indicators
- [ ] Message threading


---

## Navigation

← [Previous: broadcasting.md](broadcasting.md) | [Index](INDEX.md) | [Next: solid.md](solid.md) →

