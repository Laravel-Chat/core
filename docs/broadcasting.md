# Real-time Broadcasting with Laravel Reverb

Laravel Chat Package includes built-in support for real-time messaging using Laravel Reverb, the official first-party WebSocket server for Laravel 12.

## Table of Contents

- [Overview](#overview)
- [Installing Laravel Reverb](#installing-laravel-reverb)
- [Configuration](#configuration)
- [Frontend Setup](#frontend-setup)
- [Events](#events)
- [Broadcasting Channels](#broadcasting-channels)
- [Best Practices](#best-practices)

## Overview

Laravel Reverb is a first-party WebSocket server that provides blazing-fast and scalable real-time communication for Laravel applications. The Chat Package integrates seamlessly with Reverb for instant message delivery.

### Why Reverb?

- ✅ **First-party Laravel Solution** - Official Laravel package
- ✅ **Blazing Fast** - Built with performance in mind
- ✅ **Easy Setup** - Simple configuration
- ✅ **Horizontal Scaling** - Built for production
- ✅ **Free & Open Source** - No third-party costs

## Installing Laravel Reverb

### Step 1: Install Reverb

```bash
composer require laravel/reverb
```

### Step 2: Publish Configuration

```bash
php artisan reverb:install
```

This will:
- Publish Reverb configuration
- Update your `.env` file
- Install necessary NPM packages

### Step 3: Configure Environment

Update your `.env` file:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Step 4: Start Reverb Server

```bash
php artisan reverb:start
```

For development with debugging:

```bash
php artisan reverb:start --debug
```

For production (with supervisor):

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## Configuration

### Enable Broadcasting in Chat Package

In `config/chat.php`:

```php
'broadcasting' => [
    'enabled' => env('CHAT_BROADCASTING_ENABLED', true),
    'channel_prefix' => env('CHAT_CHANNEL_PREFIX', 'chat'),
],
```

### Configure Broadcasting Channels

Update `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;

// User's private chat channel
Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Conversation channel
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()
        ->where('conversations.id', $conversationId)
        ->exists();
});
```

## Frontend Setup

### Step 1: Install Laravel Echo

```bash
npm install --save-dev laravel-echo pusher-js
```

### Step 2: Configure Echo

In `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Step 3: Listen to Events

#### Using Echo Hooks (Laravel 12 - Recommended)

Laravel 12 introduces Echo Hooks for easier event listening without manual channel subscription:

```javascript
import { useListen } from '@laravel/echo-hooks';

// In your Vue/React component
const { data: message, error } = useListen('MessageSent', {
    channel: `chat.conversation.${conversationId}`,
    channelType: 'private',
});

// Automatically receives MessageSent events
// No manual subscription/cleanup needed
```

#### Traditional Echo Listeners

For more control, use traditional Echo API:

#### Listen for New Messages

```javascript
// Listen to a specific conversation
Echo.private(`chat.conversation.${conversationId}`)
    .listen('MessageSent', (event) => {
        console.log('New message:', event.message);
        // Update your UI
        appendMessageToChat(event.message);
    });
```

#### Listen for New Conversations

```javascript
// Listen to user's private channel for new conversations
Echo.private(`chat.user.${userId}`)
    .listen('ConversationCreated', (event) => {
        console.log('New conversation:', event.conversation);
        // Update conversation list
        addConversationToList(event.conversation);
    });
```

#### Listen for Message Read Status

```javascript
Echo.private(`chat.conversation.${conversationId}`)
    .listen('MessageRead', (event) => {
        console.log('Messages read:', event.messageIds);
        // Update read status in UI
        markMessagesAsRead(event.messageIds);
    });
```

### Vue.js Example with Echo Hooks (Laravel 12)

```vue
<template>
  <div class="chat-container">
    <div v-if="error">Error loading messages</div>
    <div v-for="message in messages" :key="message.id">
      {{ message.content }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useListen } from '@laravel/echo-hooks';
import axios from 'axios';

const props = defineProps(['conversationId', 'userId']);
const messages = ref([]);

// Use Echo Hook to listen for new messages
const { data: newMessage } = useListen('MessageSent', {
    channel: `chat.conversation.${props.conversationId}`,
    channelType: 'private',
});

// Watch for new messages
watch(newMessage, (message) => {
    if (message) {
        messages.value.push(message);
        scrollToBottom();
    }
});

onMounted(() => {
    loadMessages();
});

const loadMessages = async () => {
    const response = await axios.get(`/api/conversations/${props.conversationId}/messages`);
    messages.value = response.data.messages;
};

const scrollToBottom = () => {
    // Scroll logic
};
</script>
```

### Vue.js Example (Traditional Echo)

```vue
<template>
  <div class="chat-container">
    <div v-for="message in messages" :key="message.id">
      {{ message.content }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps(['conversationId', 'userId']);
const messages = ref([]);

let channel;

onMounted(() => {
    // Load initial messages
    loadMessages();
    
    // Subscribe to conversation channel
    channel = Echo.private(`chat.conversation.${props.conversationId}`)
        .listen('MessageSent', (event) => {
            messages.value.push(event.message);
            scrollToBottom();
        });
});

onUnmounted(() => {
    if (channel) {
        Echo.leave(`chat.conversation.${props.conversationId}`);
    }
});

const loadMessages = async () => {
    const response = await axios.get(`/api/conversations/${props.conversationId}/messages`);
    messages.value = response.data.messages;
};

const scrollToBottom = () => {
    // Scroll logic
};
</script>
```

### React Example with Echo Hooks (Laravel 12)

```javascript
import { useListen } from '@laravel/echo-hooks';
import { useEffect, useState } from 'react';
import axios from 'axios';

function ChatComponent({ conversationId, userId }) {
    const [messages, setMessages] = useState([]);
    
    // Use Echo Hook - automatically manages subscription
    const { data: newMessage } = useListen('MessageSent', {
        channel: `chat.conversation.${conversationId}`,
        channelType: 'private',
    });

    // Load initial messages
    useEffect(() => {
        loadMessages();
    }, [conversationId]);
    
    // Handle new messages
    useEffect(() => {
        if (newMessage) {
            setMessages(prev => [...prev, newMessage]);
        }
    }, [newMessage]);

    const loadMessages = async () => {
        const response = await axios.get(`/api/conversations/${conversationId}/messages`);
        setMessages(response.data.messages);
    };

    return (
        <div className="chat-container">
            {messages.map(message => (
                <div key={message.id}>{message.content}</div>
            ))}
        </div>
    );
}
```

### React Example (Traditional Echo)

```javascript
import { useEffect, useState } from 'react';
import axios from 'axios';

function ChatComponent({ conversationId, userId }) {
    const [messages, setMessages] = useState([]);

    useEffect(() => {
        // Load initial messages
        loadMessages();

        // Subscribe to conversation channel
        const channel = Echo.private(`chat.conversation.${conversationId}`)
            .listen('MessageSent', (event) => {
                setMessages(prev => [...prev, event.message]);
            });

        return () => {
            Echo.leave(`chat.conversation.${conversationId}`);
        };
    }, [conversationId]);

    const loadMessages = async () => {
        const response = await axios.get(`/api/conversations/${conversationId}/messages`);
        setMessages(response.data.messages);
    };

    return (
        <div className="chat-container">
            {messages.map(message => (
                <div key={message.id}>{message.content}</div>
            ))}
        </div>
    );
}
```

## Events

The package dispatches the following events for real-time updates:

### MessageSent

Dispatched when a new message is sent.

```php
class MessageSent implements ShouldBroadcast
{
    public function __construct(
        public Message $message,
        public Conversation $conversation
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.'.$this->conversation->id),
        ];
    }
}
```

### ConversationCreated

Dispatched when a new conversation is created.

```php
class ConversationCreated implements ShouldBroadcast
{
    public function __construct(
        public Conversation $conversation,
        public Model $participant
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.'.$this->participant->id),
        ];
    }
}
```

### MessageReceived

Dispatched when a message is received by a user.

```php
class MessageReceived implements ShouldBroadcast
{
    public function __construct(
        public Message $message,
        public Model $recipient
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.user.'.$this->recipient->id),
        ];
    }
}
```

## Broadcasting Channels

### Private Channels

All chat channels are private and require authentication:

#### User Channel
- **Format**: `chat.user.{userId}`
- **Purpose**: Receive personal notifications (new conversations, mentions)
- **Authorization**: User must match channel ID

#### Conversation Channel
- **Format**: `chat.conversation.{conversationId}`
- **Purpose**: Receive messages in a conversation
- **Authorization**: User must be a participant

### Channel Authorization

Laravel Reverb automatically uses your `routes/channels.php` definitions for authorization.

## Best Practices

### 1. Queue Event Broadcasting

For better performance, queue your broadcast events:

```php
class MessageSent implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    
    public $queue = 'broadcasts';
}
```

### 2. Optimize Event Payloads

Only broadcast necessary data:

```php
public function broadcastWith(): array
{
    return [
        'id' => $this->message->id,
        'content' => $this->message->content,
        'user' => [
            'id' => $this->message->user->id,
            'name' => $this->message->user->name,
        ],
        'created_at' => $this->message->created_at,
    ];
}
```

### 3. Handle Connection Issues

```javascript
Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('Connection error:', error);
    // Show reconnection UI
});

Echo.connector.pusher.connection.bind('connected', () => {
    console.log('Connected to Reverb');
    // Hide reconnection UI
});
```

### 4. Cleanup on Component Unmount

Always leave channels when components unmount:

```javascript
onUnmounted(() => {
    Echo.leave(`chat.conversation.${conversationId}`);
    Echo.leave(`chat.user.${userId}`);
});
```

### 5. Presence Channels (Optional)

For "user is typing" or "online status":

```php
// routes/channels.php
Broadcast::channel('chat.presence.conversation.{conversationId}', function ($user, $conversationId) {
    if ($user->conversations()->where('id', $conversationId)->exists()) {
        return ['id' => $user->id, 'name' => $user->name];
    }
});
```

```javascript
Echo.join(`chat.presence.conversation.${conversationId}`)
    .here((users) => {
        console.log('Users in conversation:', users);
    })
    .joining((user) => {
        console.log(user.name + ' joined');
    })
    .leaving((user) => {
        console.log(user.name + ' left');
    });
```

## Production Deployment

### Supervisor Configuration

Create `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
process_name=%(program_name)s
command=php /path/to/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/reverb.log
```

### Nginx Configuration

```nginx
location /app {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### SSL/TLS Configuration

For production, use SSL:

```env
REVERB_SCHEME=https
REVERB_PORT=443
```

Update Nginx with SSL certificate and proxy to Reverb.

## Troubleshooting

### Connection Refused

1. Check if Reverb server is running: `php artisan reverb:start --debug`
2. Verify firewall allows port 8080
3. Check `.env` configuration

### Authentication Failed

1. Verify `routes/channels.php` authorization logic
2. Check if user is authenticated
3. Ensure CSRF token is included in requests

### Messages Not Broadcasting

1. Verify broadcasting is enabled in `config/chat.php`
2. Check queue workers are running: `php artisan queue:work`
3. Ensure events implement `ShouldBroadcast`

### Performance Issues

1. Use queue workers for broadcasting
2. Optimize event payloads (use `broadcastWith()`)
3. Consider horizontal scaling with Reverb clustering


---

## Navigation

← [Previous: policies.md](policies.md) | [Index](INDEX.md) | [Next: architecture.md](architecture.md) →

