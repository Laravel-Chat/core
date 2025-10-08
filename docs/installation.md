# Installation Guide

This guide will walk you through installing and setting up the Laravel Chat package.

## Requirements

Before installing, make sure your environment meets these requirements:

- **PHP**: 8.4 or higher
- **Laravel**: 12.x
- **Database**: MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.8+
- **Extensions**: PDO, Mbstring, JSON

## Step-by-Step Installation

### 1. Install via Composer

```bash
composer require akira/laravel-chat
```

### 2. Publish Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --tag=laravel-chat-config
```

This creates `config/chat.php` with all configuration options.

### 3. Publish Migrations

Publish the database migrations:

```bash
php artisan vendor:publish --tag=laravel-chat-migrations
```

This copies migration files to your `database/migrations` directory.

### 4. Run Migrations

Execute the migrations to create necessary tables:

```bash
php artisan migrate
```

This creates:
- `conversations` - Stores conversation data
- `messages` - Stores message data
- `conversation_participants` - Links users to conversations

### 5. Add Trait to User Model

Add the `HasConversations` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Akira\LaravelChat\Traits\HasConversations;

class User extends Authenticatable
{
    use HasConversations;
    
    // Your existing code...
}
```

This adds methods like:
- `conversations()` - Relationship to conversations
- `unreadMessagesCount()` - Count unread messages
- `canSendMessagesTo($user)` - Check permissions
- `canReceiveMessagesFrom($user)` - Check permissions

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# User Model (optional, defaults to App\Models\User)
CHAT_USER_MODEL=App\Models\User

# Broadcasting (optional, defaults to true)
CHAT_BROADCASTING_ENABLED=true
CHAT_CHANNEL_PREFIX=chat

# Message Policy (optional)
CHAT_MESSAGE_POLICY=
```

### Basic Configuration

The default configuration in `config/chat.php` looks like this:

```php
<?php

return [
    'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),
    
    'models' => [
        'conversation' => Akira\LaravelChat\Models\Conversation::class,
        'message' => Akira\LaravelChat\Models\Message::class,
        'conversation_participant' => Akira\LaravelChat\Models\ConversationParticipant::class,
    ],
    
    'tables' => [
        'conversations' => 'conversations',
        'messages' => 'messages',
        'conversation_participants' => 'conversation_participants',
    ],
    
    'broadcasting' => [
        'enabled' => env('CHAT_BROADCASTING_ENABLED', true),
        'channel_prefix' => env('CHAT_CHANNEL_PREFIX', 'chat'),
    ],
    
    'pagination' => [
        'conversations_per_page' => 20,
        'messages_per_page' => 50,
    ],
    
    'message_types' => [
        'text',
        'image',
        'file',
        'system',
    ],
    
    'conversation_types' => [
        'direct',
        'group',
    ],
    
    'policies' => [
        'message_policy' => env('CHAT_MESSAGE_POLICY', null),
    ],
];
```

## Verification

### Test Installation

Create a simple test to verify installation:

```php
use Akira\LaravelChat\Facades\Conversation;
use App\Models\User;

// Get two users
$user1 = User::find(1);
$user2 = User::find(2);

// Create a conversation
$conversation = Conversation::getOrCreateDirect($user1, $user2);

// Check if successful
if ($conversation) {
    echo "Installation successful! Conversation ID: {$conversation->id}";
}
```

### Check Database

Verify tables were created:

```bash
php artisan db:show
```

You should see:
- `conversations`
- `messages`
- `conversation_participants`

## Optional: Broadcasting Setup

If you want real-time functionality, set up Laravel Broadcasting:

### 1. Install Laravel Echo and Pusher

```bash
npm install --save laravel-echo pusher-js
```

### 2. Configure Broadcasting

In `config/broadcasting.php`:

```php
'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true,
        ],
    ],
],
```

### 3. Update .env

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### 4. Setup Laravel Echo

In your `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    encrypted: true
});
```

See [Broadcasting Guide](broadcasting.md) for detailed setup.

## Troubleshooting

### Migration Errors

**Error: "Table already exists"**

Solution:
```bash
php artisan migrate:rollback --step=3
php artisan migrate
```

**Error: "Foreign key constraint fails"**

Solution: Make sure your users table exists before running chat migrations.

### Trait Not Found

**Error: "Trait 'HasConversations' not found"**

Solution:
```bash
composer dump-autoload
php artisan optimize:clear
```

### Configuration Not Loaded

**Error: Configuration values not taking effect**

Solution:
```bash
php artisan config:clear
php artisan config:cache
```

## Next Steps

Now that installation is complete:

1. **[Quick Start Guide](quickstart.md)** - Learn basic usage
2. **[Facades Documentation](facades.md)** - Explore the API
3. **[Configuration Guide](configuration.md)** - Customize settings
4. **[Examples](examples.md)** - See real-world examples

## Upgrading

To upgrade to a new version:

```bash
# Update via Composer
composer update akira/laravel-chat

# Republish config (optional)
php artisan vendor:publish --tag=laravel-chat-config --force

# Run new migrations (if any)
php artisan migrate
```

## Uninstallation

To remove the package:

```bash
# Remove via Composer
composer remove akira/laravel-chat

# Remove migrations (optional)
php artisan migrate:rollback --step=3

# Remove config (optional)
rm config/chat.php
```


---

## Navigation

← [Previous: INDEX.md](INDEX.md) | [Index](INDEX.md) | [Next: quickstart.md](quickstart.md) →

