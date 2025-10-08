# SOLID Principles

This document explains how the Laravel Chat Package applies SOLID principles to create maintainable, testable, and extensible code.

## Overview of SOLID

SOLID is an acronym for five design principles:

- **S**ingle Responsibility Principle
- **O**pen/Closed Principle
- **L**iskov Substitution Principle
- **I**nterface Segregation Principle
- **D**ependency Inversion Principle

## Single Responsibility Principle (SRP)

> A class should have only one reason to change.

### Implementation

Each action class has exactly one responsibility:

```php
// ✅ Good - One responsibility: Create conversations
class CreateConversationAction
{
    public function handle(Model $creator, string $type, array $participantIds): CreateConversationResult
    {
        // Only handles conversation creation
    }
}

// ✅ Good - One responsibility: Send messages
class SendMessageAction
{
    public function handle(Model $user, int $conversationId, string $content): Message
    {
        // Only handles sending messages
    }
}

// ✅ Good - One responsibility: Find direct conversations
class FindDirectConversationAction
{
    public function handle(Model $user1, Model $user2): ?Conversation
    {
        // Only finds existing direct conversations
    }
}
```

### Benefits

- Easy to understand and maintain
- Changes to one feature don't affect others
- Simple to test in isolation
- Clear naming and purpose

### Counter-Example

```php
// ❌ Bad - Multiple responsibilities
class ConversationService
{
    public function createConversation() { }
    public function deleteConversation() { }
    public function sendMessage() { }
    public function markAsRead() { }
    public function getMessages() { }
    public function broadcastEvent() { }
    public function validateUser() { }
}
```

## Open/Closed Principle (OCP)

> Software entities should be open for extension but closed for modification.

### Implementation: Message Policies

The package allows custom permission logic without modifying core code:

```php
// Interface defines the contract
interface MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool;
    public function canReceiveMessage(Model $recipient, Model $sender): bool;
}

// Default implementation (closed for modification)
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

// Extended functionality (open for extension)
class FollowerMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        return $sender->isFollowing($recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        return $recipient->privacy_accepts_messages ?? true;
    }
}
```

### Configuration

```php
// config/chat.php
'policies' => [
    'message_policy' => FollowerMessagePolicy::class, // Extend without modifying
]
```

### Benefits

- Add new policies without changing existing code
- Core package remains stable
- Easy to customize for different use cases

## Liskov Substitution Principle (LSP)

> Objects should be replaceable with instances of their subtypes without altering correctness.

### Implementation: Value Objects with Contracts

All value objects implement the same contract and can be used interchangeably:

```php
// Contract defines the interface
interface ValueObjectContract extends Arrayable, Collectable, Jsonable
{
    // All value objects implement this
}

// Different value objects, same contract
class ConversationResult implements ValueObjectContract, JsonSerializable
{
    public function toArray(): array { /* ... */ }
    public function toCollection(): Collection { /* ... */ }
    public function toJson(int $options = 0): string { /* ... */ }
}

class MessageResult implements ValueObjectContract, JsonSerializable
{
    public function toArray(): array { /* ... */ }
    public function toCollection(): Collection { /* ... */ }
    public function toJson(int $options = 0): string { /* ... */ }
}

// All can be used interchangeably in code expecting ValueObjectContract
function processResult(ValueObjectContract $result): array
{
    return $result->toArray(); // Works with any implementation
}

// Usage - all behave consistently
$conversationData = $conversation->toArray();
$conversationCollection = $conversation->toCollection();
$conversationJson = $conversation->toJson();

$messageData = $message->toArray();
$messageCollection = $message->toCollection();
$messageJson = $message->toJson();
```

### Benefits

- **No inheritance** - Avoid fragile base class problem
- **Predictable behavior** - Contract enforced
- **Type safety** - Compiler/IDE checks compliance
- **Easy substitution** - Swap implementations safely

### Model Substitution

Custom models can replace default models:

```php
// Your custom model
class CustomConversation extends Conversation
{
    // Add custom methods
    public function getDisplayName(): string
    {
        return $this->title ?: 'Chat';
    }
}

// Configuration
'models' => [
    'conversation' => CustomConversation::class,
]

// Works seamlessly throughout the package
$conversation = $config->getConversationModel()::find($id);
// Returns CustomConversation instance with all base functionality
```

### Benefits

- Predictable behavior across implementations
- Safe to extend without breaking existing code
- Type safety maintained

## Interface Segregation Principle (ISP)

> Clients should not be forced to depend on interfaces they don't use.

### Implementation: Focused Contracts

The package defines small, focused contracts:

```php
// ✅ Good - Small, focused contract
interface MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool;
    public function canReceiveMessage(Model $recipient, Model $sender): bool;
}

// ✅ Good - Specific value object contract
interface ValueObjectContract
{
    public function toArray(): array;
    public function toCollection(): Collection;
    public function toJson(int $options = 0): string;
}
```

### Counter-Example

```php
// ❌ Bad - Too many methods, clients forced to implement all
interface ChatContract
{
    public function createConversation();
    public function deleteConversation();
    public function sendMessage();
    public function deleteMessage();
    public function markAsRead();
    public function getConversations();
    public function getMessages();
    public function broadcastMessage();
    public function canSendMessage();
    public function validateUser();
    // ... many more
}
```

### Benefits

- Implement only what you need
- Smaller, more cohesive contracts
- Easier to test and mock
- Clear contracts

## Dependency Inversion Principle (DIP)

> Depend on abstractions, not concretions.

### Implementation: Dependency Injection

Actions depend on abstractions (interfaces and contracts), not concrete implementations:

```php
// ✅ Good - Depends on ChatConfig abstraction
class CreateConversationAction
{
    public function __construct(
        private ChatConfig $config,  // Abstraction
        private FindDirectConversationAction $findDirectAction  // Abstraction
    ) {}

    public function handle(Model $creator, string $type, array $participantIds): CreateConversationResult
    {
        // Use injected dependencies
        $conversationModel = $this->config->getConversationModel();
        $existing = $this->findDirectAction->handle($creator, $otherUser);
    }
}
```

### Configuration Layer

The ChatConfig provides an abstraction layer over configuration:

```php
// ✅ Good - Abstraction
class SendMessageAction
{
    public function __construct(private ChatConfig $config) {}

    public function handle()
    {
        $messageModel = $this->config->getMessageModel();
        $enabled = $this->config->isBroadcastingEnabled();
    }
}

// ❌ Bad - Direct dependency on concrete config
class SendMessageAction
{
    public function handle()
    {
        $messageModel = config('chat.models.message'); // Concrete
        $enabled = config('chat.broadcasting.enabled'); // Concrete
    }
}
```

### Policy Abstraction

```php
// ✅ Good - Depend on interface
class CreateConversationAction
{
    public function handle()
    {
        $policy = $this->config->getMessagePolicy(); // Returns MessagePolicyContract
        
        if ($policy && !$policy->canReceiveMessage($recipient, $sender)) {
            throw new Exception('User does not accept messages');
        }
    }
}
```

### Benefits

- Easy to test with mocks
- Flexible configuration
- Loose coupling
- Easy to swap implementations

## Real-World Examples

### Example 1: Adding a Custom Policy

Following SOLID principles, adding a custom policy is straightforward:

```php
// 1. Create class implementing interface (OCP, DIP)
class PrivacySettingsPolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Check sender's privacy settings
        return $sender->can_send_messages ?? true;
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Check recipient's privacy settings
        return $recipient->accepts_messages_from === 'everyone' ||
               ($recipient->accepts_messages_from === 'followers' && 
                $recipient->isFollowedBy($sender));
    }
}

// 2. Register in config
'policies' => [
    'message_policy' => PrivacySettingsPolicy::class,
]

// 3. Works automatically throughout the package (LSP)
```

### Example 2: Custom Conversation Model

```php
// 1. Extend base model (LSP)
class TeamConversation extends Conversation
{
    // Add specific methods (SRP)
    public function addTeamMember(User $user): void
    {
        $this->participants()->attach($user->id, [
            'joined_at' => now(),
            'is_admin' => false,
        ]);
    }

    public function isTeamAdmin(User $user): bool
    {
        return $this->participants()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('is_admin', true)
            ->exists();
    }
}

// 2. Configure
'models' => [
    'conversation' => TeamConversation::class,
]

// 3. Use throughout package (DIP)
```

### Example 3: Custom Action

```php
// 1. Create focused action (SRP)
class PinMessageAction
{
    public function __construct(
        private ChatConfig $config  // DIP
    ) {}

    public function handle(Model $user, int $messageId): void
    {
        $messageModel = $this->config->getMessageModel();
        $message = $messageModel::findOrFail($messageId);

        // Verify user is in conversation
        if (!$message->conversation->participants->contains($user)) {
            throw new UnauthorizedException();
        }

        $message->update(['is_pinned' => true]);
    }
}

// 2. Inject into manager (DIP)
class MessageManager
{
    public function __construct(
        private PinMessageAction $pinAction
    ) {}

    public function pin(Model $user, int $messageId): void
    {
        $this->pinAction->handle($user, $messageId);
    }
}
```

## Testing with SOLID

SOLID principles make testing easier:

```php
// SRP - Test one thing
test('creates direct conversation', function () {
    $action = new CreateConversationAction($config, $findAction);
    $result = $action->handle($user, 'direct', [$recipient->id]);
    
    expect($result)->toBeInstanceOf(CreateConversationResult::class);
});

// DIP - Easy to mock dependencies
test('checks policy before creating conversation', function () {
    $mockPolicy = Mockery::mock(MessagePolicyContract::class);
    $mockPolicy->shouldReceive('canReceiveMessage')->andReturn(false);
    
    $config = new ChatConfig(messagePolicy: $mockPolicy);
    $action = new CreateConversationAction($config, $findAction);
    
    expect(fn() => $action->handle($user, 'direct', [$recipient->id]))
        ->toThrow(Exception::class);
});

// LSP - Substitute implementations
test('works with custom conversation model', function () {
    config(['chat.models.conversation' => CustomConversation::class]);
    
    $result = Conversation::create($user, 'direct', [$recipient->id]);
    
    expect($result)->toBeInstanceOf(CreateConversationResult::class);
});
```

## Benefits Summary

### Maintainability

- Changes are localized (SRP)
- Easy to understand code
- Clear responsibilities

### Extensibility

- Add features without modifying existing code (OCP)
- Custom implementations (LSP)

### Testability

- Mock dependencies easily (DIP)
- Test in isolation (SRP)
- Predictable behavior (LSP)

### Flexibility

- Swap implementations (DIP)
- Configure without code changes (OCP)
- Small, focused contracts (ISP)

## Common Patterns

### Pattern: Action + Dependency Injection

```php
class MyAction
{
    // DIP - Depend on abstractions
    public function __construct(
        private ChatConfig $config,
        private OtherAction $otherAction
    ) {}

    // SRP - Single responsibility
    public function handle(/* params */): ValueObjectContract
    {
        // Implementation
    }
}
```

### Pattern: Contract + Multiple Implementations

```php
// ISP - Focused contract
interface PolicyContract
{
    public function check(): bool;
}

// OCP - Extend without modifying
class DefaultPolicy implements PolicyContract { }
class CustomPolicy implements PolicyContract { }

// DIP - Depend on contract
class Action
{
    public function __construct(private PolicyContract $policy) {}
}
```

### Pattern: Value Object with Contracts

```php
// ISP - Contract defines capabilities
interface ValueObjectContract extends Arrayable, Collectable, Jsonable
{
    // Focused contract
}

// LSP - All implementations are substitutable
class ConversationResult implements ValueObjectContract, JsonSerializable { }
class MessageResult implements ValueObjectContract, JsonSerializable { }

// DIP - Depend on contract, not concrete classes
function transformResult(ValueObjectContract $result): array
{
    return $result->toArray();
}
```

## Anti-Patterns to Avoid

### ❌ God Classes

```php
// Violates SRP
class ChatService
{
    public function createConversation() { }
    public function sendMessage() { }
    public function getMessages() { }
    // ... 50 more methods
}
```

### ❌ Concrete Dependencies

```php
// Violates DIP
class Action
{
    public function handle()
    {
        $config = config('chat'); // Direct config access
        $model = new Conversation(); // Direct instantiation
    }
}
```

### ❌ Bloated Contracts

```php
// Violates ISP
interface ChatContract
{
    public function method1();
    public function method2();
    // ... 20 more methods
}
```

## Conclusion

The Laravel Chat Package demonstrates how SOLID principles create:

- **Maintainable** code through single responsibilities
- **Extensible** architecture through interfaces and abstractions
- **Testable** components through dependency injection
- **Flexible** design through polymorphism

By following these principles, the package remains stable while allowing unlimited customization.


---

## Navigation

← [Previous: architecture.md](architecture.md) | [Index](INDEX.md) | [Next: actions.md](actions.md) →
