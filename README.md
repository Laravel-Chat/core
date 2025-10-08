# Laravel Chat Package

[![Tests](https://img.shields.io/badge/tests-82%20passing-brightgreen)](https://github.com/akira/laravel-chat)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://phpstan.org/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-blue)](https://php.net)

A modern, feature-rich Laravel package for implementing real-time chat functionality with support for direct and group conversations, message policies, and real-time broadcasting.

## ✨ Features

- 🔥 **Direct & Group Conversations** - Support for both one-on-one and group chats
- 🎯 **SOLID Architecture** - Clean, maintainable code following SOLID principles
- 🚀 **Facades API** - Expressive and intuitive API using Laravel facades
- 🔒 **Message Policies** - Configurable permission system for message sending
- 📡 **Real-time Broadcasting** - Built-in support for Laravel Echo and Pusher
- 🧪 **Fully Tested** - 82 tests with 211 assertions (100% passing)
- 📝 **Type-Safe** - Full type hints and PHPStan level max compliance
- 🎨 **Extensible** - Easy to customize and extend
- ⚡ **Performance** - Optimized queries and singleton pattern
- 📚 **Well Documented** - Comprehensive documentation and examples

## 📋 Requirements

- PHP 8.4 or higher
- Laravel 12.x
- MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.8+

## 🚀 Quick Start

### Installation

```bash
composer require akira/laravel-chat
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=laravel-chat-config
php artisan vendor:publish --tag=laravel-chat-migrations
```

### Run Migrations

```bash
php artisan migrate
```

### Add Trait to User Model

```php
use Akira\LaravelChat\Traits\HasConversations;

class User extends Authenticatable
{
    use HasConversations;
}
```

### Basic Usage

```php
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;

// Create a direct conversation
$conversation = Conversation::getOrCreateDirect($user1, $user2);

// Send a message
$message = Message::send($user1, $conversation->id, 'Hello!');

// Get user conversations
$conversations = Conversation::getUserConversations($user);

// Mark messages as read
Message::markAsRead($user, $conversation->id);
```

## 📖 Documentation

### Complete Guides

- **[Installation Guide](docs/installation.md)** - Detailed installation steps
- **[Quick Start](docs/quickstart.md)** - Get started in 5 minutes
- **[Facades API](docs/facades.md)** - Complete facade reference
- **[Configuration](docs/configuration.md)** - All configuration options
- **[Message Policies](docs/policies.md)** - Permission system
- **[Broadcasting](docs/broadcasting.md)** - Real-time setup
- **[Testing](docs/testing.md)** - How to test your chat implementation
- **[Advanced Usage](docs/advanced.md)** - Advanced patterns and customization
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Examples](docs/examples.md)** - Real-world examples

### Architecture

- **[Architecture Overview](docs/architecture.md)** - Package structure and design
- **[SOLID Principles](docs/solid.md)** - How SOLID is applied
- **[Action Pattern](docs/actions.md)** - Understanding the action pattern

## 🎯 Core Concepts

### Conversations

Conversations are the containers for messages. They can be:
- **Direct**: One-on-one chat between two users
- **Group**: Multi-user conversation

### Messages

Messages belong to conversations and have:
- Content and type (text, image, file, system)
- Read/unread status
- Metadata support for rich content

### Policies

Configurable permission system to control who can send messages to whom.

## 💡 Example Usage

### Creating Conversations

```php
use Akira\LaravelChat\Facades\Conversation;

// Direct conversation
$result = Conversation::create($creator, 'direct', [$recipientId]);

// Group conversation
$result = Conversation::create(
    $creator,
    'group',
    [$user2Id, $user3Id, $user4Id],
    'Project Team'
);
```

### Sending Messages

```php
use Akira\LaravelChat\Facades\Message;

// Simple text message
$message = Message::send($user, $conversationId, 'Hello!');

// Message with file attachment
$message = Message::send(
    $user,
    $conversationId,
    'Check this file',
    'file',
    [
        'file_url' => 'https://example.com/document.pdf',
        'file_name' => 'document.pdf',
        'file_size' => 1024000,
    ]
);
```

### Checking Permissions

```php
// Check if user can send messages to another user
if ($sender->canSendMessagesTo($recipient)) {
    // Create conversation
}

// Check if user accepts messages
if ($recipient->canReceiveMessagesFrom($sender)) {
    // Send message
}
```

## 🔧 Configuration

Key configuration options in `config/chat.php`:

```php
return [
    // User model
    'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),
    
    // Message policy (null = allow all)
    'policies' => [
        'message_policy' => null,
        // or use custom policy
        // 'message_policy' => \App\Policies\CustomMessagePolicy::class,
    ],
    
    // Broadcasting
    'broadcasting' => [
        'enabled' => env('CHAT_BROADCASTING_ENABLED', true),
        'channel_prefix' => env('CHAT_CHANNEL_PREFIX', 'chat'),
    ],
    
    // Pagination
    'pagination' => [
        'conversations_per_page' => 20,
        'messages_per_page' => 50,
    ],
];
```

## 🧪 Testing

The package includes comprehensive tests:

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Run PHPStan
composer phpstan

# Run Rector
composer rector
```

**Test Stats:**
- ✅ 82 tests passing
- ✅ 211 assertions
- ✅ PHPStan level max
- ✅ 100% coverage for critical paths

## 🎨 Customization

### Custom Message Policy

```php
<?php

namespace App\Policies;

use Akira\LaravelChat\Policies\MessagePolicyContract;
use Illuminate\Database\Eloquent\Model;

class CustomMessagePolicy implements MessagePolicyContract
{
    public function canSendMessage(Model $sender, Model $recipient): bool
    {
        // Your custom logic
        return $sender->isFollowing($recipient);
    }

    public function canReceiveMessage(Model $recipient, Model $sender): bool
    {
        // Your custom logic
        return $recipient->privacy_accepts_messages;
    }
}
```

Register in `config/chat.php`:
```php
'policies' => [
    'message_policy' => \App\Policies\CustomMessagePolicy::class,
],
```

## 🚦 Events

The package dispatches events for real-time functionality:

- `ConversationCreated` - When a conversation is created
- `MessageSent` - When a message is sent
- `MessageReceived` - When a message is received

Listen to these events for custom logic:

```php
use Akira\LaravelChat\Events\MessageSent;

Event::listen(MessageSent::class, function ($event) {
    // Send notification, update UI, etc.
});
```

## 🔒 Security

### Authentication

All operations require authenticated users. The package uses Laravel's authentication system.

### Authorization

- Only conversation participants can send/read messages
- Only conversation creator can delete conversations
- Configurable message policies for fine-grained control

### Validation

- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM
- XSS protection on message content

## 📊 Performance

### Optimizations

- ✅ Singleton pattern for configuration
- ✅ Eager loading to prevent N+1 queries
- ✅ Database indexes on foreign keys
- ✅ Efficient query scopes
- ✅ Lazy loading of relationships

### Best Practices

- Use pagination for large conversation lists
- Implement message batching for bulk operations
- Cache frequently accessed data
- Use queues for non-critical operations

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone repository
git clone https://github.com/laravel-chat/core.git

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer phpstan
composer rector
```

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 📄 License

The Laravel Chat Package is open-sourced software licensed under the [MIT license](LICENSE.md).

## 🙏 Credits

- **Author**:  [Kidiatoliny](https://github.com/kidiatoliny)
- **Contributors**: [All Contributors](https://github.com/akira/laravel-chat/contributors)

## 💬 Support

- **Documentation**: [Full Documentation](docs/)
- **Issues**: [GitHub Issues](https://github.com/laravel-chat/core/issues)
- **Discussions**: [GitHub Discussions](https://github.com/laravel-chat/core/discussions)

## 🌟 Star History

If you find this package useful, please consider giving it a star on GitHub!

---

**Made with ❤️ by the Akira team**
