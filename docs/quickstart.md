# Quick Start Guide

Get up and running with Laravel Chat in 5 minutes!

## Prerequisites

- Laravel 12.x application running
- PHP 8.4+
- Database configured

## Installation

### 1. Install Package

```bash
composer require akira/laravel-chat
```

### 2. Publish & Migrate

```bash
php artisan vendor:publish --tag=laravel-chat-config
php artisan vendor:publish --tag=laravel-chat-migrations
php artisan migrate
```

### 3. Add Trait

Add `HasConversations` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Akira\LaravelChat\Traits\HasConversations;

class User extends Authenticatable
{
    use HasConversations;
}
```

## Your First Conversation

Let's create a simple chat between two users!

### 1. Create a Conversation

```php
use Akira\LaravelChat\Facades\Conversation;

// Get two users
$user1 = User::find(1);
$user2 = User::find(2);

// Create or get existing direct conversation
$conversation = Conversation::getOrCreateDirect($user1, $user2);

echo "Conversation created! ID: {$conversation->id}";
```

### 2. Send a Message

```php
use Akira\LaravelChat\Facades\Message;

$message = Message::send($user1, $conversation->id, 'Hello, World!');

echo "Message sent! ID: {$message->id}";
```

### 3. Get Messages

```php
$data = Message::getConversationMessages($user1, $conversation->id);

foreach ($data['messages'] as $msg) {
    echo "{$msg['user']['name']}: {$msg['content']}\n";
}
```

### 4. Mark as Read

```php
Message::markAsRead($user2, $conversation->id);
```

## Complete Example

Here's a complete working example:

```php
<?php

namespace App\Http\Controllers;

use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Show chat interface
     */
    public function index()
    {
        $user = auth()->user();
        $conversations = Conversation::getUserConversations($user);
        
        return view('chat.index', [
            'conversations' => $conversations
        ]);
    }
    
    /**
     * Show specific conversation
     */
    public function show($conversationId)
    {
        $user = auth()->user();
        
        // Validate user is participant
        if (!Conversation::validateParticipant($conversationId, $user)) {
            abort(403, 'You are not a participant in this conversation');
        }
        
        // Get messages
        $data = Message::getConversationMessages($user, $conversationId);
        
        // Mark as read
        Message::markAsRead($user, $conversationId);
        
        return view('chat.show', $data);
    }
    
    /**
     * Send a message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'message' => 'required|string|max:5000'
        ]);
        
        $user = auth()->user();
        
        $message = Message::send(
            $user,
            $conversationId,
            $request->message
        );
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    /**
     * Start new conversation
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        
        $user = auth()->user();
        $recipient = User::findOrFail($request->user_id);
        
        // Check if user can send messages
        if (!$user->canSendMessagesTo($recipient)) {
            return response()->json([
                'error' => 'You cannot send messages to this user'
            ], 403);
        }
        
        $conversation = Conversation::getOrCreateDirect($user, $recipient);
        
        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id
        ]);
    }
}
```

## Routes

Add these routes to your `routes/web.php`:

```php
use App\Http\Controllers\ChatController;

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/start', [ChatController::class, 'startConversation'])->name('chat.start');
});
```

## Basic Views

### Conversation List (`resources/views/chat/index.blade.php`)

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Conversations</h1>
    
    @foreach($conversations as $conversation)
        <div class="conversation-item">
            <a href="{{ route('chat.show', $conversation['id']) }}">
                <h3>{{ $conversation['title'] }}</h3>
                @if($conversation['unread_count'] > 0)
                    <span class="badge">{{ $conversation['unread_count'] }}</span>
                @endif
                @if($conversation['last_message'])
                    <p>{{ $conversation['last_message']['content'] }}</p>
                @endif
            </a>
        </div>
    @endforeach
</div>
@endsection
```

### Conversation View (`resources/views/chat/show.blade.php`)

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $title ?? 'Chat' }}</h1>
    
    <div id="messages">
        @foreach($messages as $message)
            <div class="message {{ $message['user']['id'] == auth()->id() ? 'sent' : 'received' }}">
                <strong>{{ $message['user']['name'] }}:</strong>
                <p>{{ $message['content'] }}</p>
                <small>{{ $message['created_at'] }}</small>
            </div>
        @endforeach
    </div>
    
    <form id="message-form" action="{{ route('chat.send', $id) }}" method="POST">
        @csrf
        <input type="text" name="message" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
// Simple AJAX submission
document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            message: this.message.value
        })
    })
    .then(response => response.json())
    .then(data => {
        // Add message to DOM
        console.log('Message sent:', data);
        this.message.value = '';
        // Reload or append message
        location.reload();
    });
});
</script>
@endsection
```

## Group Conversations

Creating a group conversation:

```php
use Akira\LaravelChat\Facades\Conversation;

$creator = auth()->user();
$memberIds = [2, 3, 4, 5]; // User IDs

$result = Conversation::create(
    $creator,
    'group',
    $memberIds,
    'Project Team Chat'
);

$conversationId = $result['id'];
```

## File Attachments

Sending a message with file metadata:

```php
use Akira\LaravelChat\Facades\Message;

$file = $request->file('attachment');
$path = $file->store('chat-files', 's3');

$message = Message::send(
    auth()->user(),
    $conversationId,
    "Attached: {$file->getClientOriginalName()}",
    'file',
    [
        'file_url' => Storage::url($path),
        'file_name' => $file->getClientOriginalName(),
        'file_size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
    ]
);
```

## Next Steps

Now that you have the basics working:

1. **[Configuration Guide](configuration.md)** - Customize settings
2. **[Facades API](facades.md)** - Learn all available methods
3. **[Broadcasting Guide](broadcasting.md)** - Add real-time updates
4. **[Message Policies](policies.md)** - Control who can message whom
5. **[Examples](examples.md)** - See more complex examples

## Common Issues

### "Conversation not found"

Make sure the user is a participant:

```php
if (!Conversation::validateParticipant($conversationId, $user)) {
    // User is not a participant
}
```

### "User cannot send messages"

Check message policies:

```php
if (!$sender->canSendMessagesTo($recipient)) {
    // Sending not allowed by policy
}
```

### Unread count not updating

Make sure to call `markAsRead`:

```php
Message::markAsRead($user, $conversationId);
```

## Tips

- Always validate user is a participant before showing messages
- Use `validateParticipant()` in your controller middleware
- Implement pagination for large conversation lists
- Cache conversation lists for better performance
- Use Laravel queues for notifications


---

## Navigation

← [Previous: installation.md](installation.md) | [Index](INDEX.md) | [Next: configuration.md](configuration.md) →


