# Configuration Guide

Complete guide to configuring the Laravel Chat package.

## Configuration File

After publishing, the configuration file is located at `config/chat.php`.

## User Model

Specify which model represents users in your application:

```php
'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),
```

**Environment Variable:**
```env
CHAT_USER_MODEL=App\Models\User
```

## Models

Override default models if you need custom implementations:

```php
'models' => [
    'conversation' => Akira\LaravelChat\Models\Conversation::class,
    'message' => Akira\LaravelChat\Models\Message::class,
    'conversation_participant' => Akira\LaravelChat\Models\ConversationParticipant::class,
],
```

### Custom Models

To use custom models, extend the package models:

```php
<?php

namespace App\Models\Chat;

use Akira\LaravelChat\Models\Conversation as BaseConversation;

class Conversation extends BaseConversation
{
    // Add custom methods or properties
}
```

Then update config:
```php
'models' => [
    'conversation' => App\Models\Chat\Conversation::class,
],
```

## Table Names

Customize database table names:

```php
'tables' => [
    'conversations' => 'conversations',
    'messages' => 'messages',
    'conversation_participants' => 'conversation_participants',
],
```

## Broadcasting

Configure real-time broadcasting:

```php
'broadcasting' => [
    'enabled' => env('CHAT_BROADCASTING_ENABLED', true),
    'channel_prefix' => env('CHAT_CHANNEL_PREFIX', 'chat'),
],
```

**Environment Variables:**
```env
CHAT_BROADCASTING_ENABLED=true
CHAT_CHANNEL_PREFIX=chat
```

### Disabling Broadcasting

For development or if you don't need real-time updates:

```env
CHAT_BROADCASTING_ENABLED=false
```

## Pagination

Set default pagination limits:

```php
'pagination' => [
    'conversations_per_page' => 20,
    'messages_per_page' => 50,
],
```

## Message Types

Define allowed message types:

```php
'message_types' => [
    'text',
    'image',
    'file',
    'system',
],
```

### Adding Custom Types

```php
'message_types' => [
    'text',
    'image',
    'file',
    'system',
    'video',      // Custom
    'audio',      // Custom
    'location',   // Custom
],
```

## Conversation Types

Define allowed conversation types:

```php
'conversation_types' => [
    'direct',
    'group',
],
```

### Adding Custom Types

```php
'conversation_types' => [
    'direct',
    'group',
    'channel',    // Custom
    'broadcast',  // Custom
],
```

## Message Policies

Configure permission system:

```php
'policies' => [
    'message_policy' => env('CHAT_MESSAGE_POLICY', null),
],
```

**Options:**
- `null` - Allow all messages (default)
- `Akira\LaravelChat\Policies\DefaultMessagePolicy::class` - Same as null
- `Akira\LaravelChat\Policies\FollowerMessagePolicy::class` - Only between followers
- Your custom policy class

**Environment Variable:**
```env
CHAT_MESSAGE_POLICY=App\Policies\CustomMessagePolicy
```

### Creating Custom Policy

See [Message Policies](policies.md) for detailed guide.

## Complete Configuration Example

```php
<?php

return [
    // User Model
    'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),
    
    // Override Models
    'models' => [
        'conversation' => Akira\LaravelChat\Models\Conversation::class,
        'message' => Akira\LaravelChat\Models\Message::class,
        'conversation_participant' => Akira\LaravelChat\Models\ConversationParticipant::class,
    ],
    
    // Table Names
    'tables' => [
        'conversations' => 'conversations',
        'messages' => 'messages',
        'conversation_participants' => 'conversation_participants',
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
    
    // Message Types
    'message_types' => [
        'text',
        'image',
        'file',
        'system',
    ],
    
    // Conversation Types
    'conversation_types' => [
        'direct',
        'group',
    ],
    
    // Policies
    'policies' => [
        'message_policy' => env('CHAT_MESSAGE_POLICY', null),
    ],
];
```

## Environment Variables Summary

Add these to your `.env` file:

```env
# Chat Configuration
CHAT_USER_MODEL=App\Models\User
CHAT_BROADCASTING_ENABLED=true
CHAT_CHANNEL_PREFIX=chat
CHAT_MESSAGE_POLICY=
```

## Caching Configuration

After changes, clear and cache config:

```bash
php artisan config:clear
php artisan config:cache
```


---

## Navigation

← [Previous: quickstart.md](quickstart.md) | [Index](INDEX.md) | [Next: facades.md](facades.md) →

