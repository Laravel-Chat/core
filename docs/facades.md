# Facades API Reference

Complete reference for the Conversation and Message facades.

## Overview

The Laravel Chat Package provides two main facades for interacting with the chat system:

- **Conversation Facade** - Manage conversations
- **Message Facade** - Send and retrieve messages

Both facades provide a clean, Laravel-style API over the underlying action-based architecture.

## Conversation Facade

```php
use Akira\LaravelChat\Facades\Conversation;
```

### Methods

#### `create(Model $creator, string $type, array $participantIds, ?string $title = null): CreateConversationResult`

Creates a new conversation or returns an existing direct conversation.

**Parameters:**
- `$creator` (Model): The user creating the conversation
- `$type` (string): `'direct'` or `'group'`
- `$participantIds` (array): Array of user IDs to add as participants
- `$title` (string|null): Optional title for group conversations (required for group chats)

**Returns:** `CreateConversationResult` value object

**Example:**
```php
use Akira\LaravelChat\Facades\Conversation;

// Create direct conversation
$result = Conversation::create($user, 'direct', [$recipientId]);

if ($result->wasCreated()) {
    // New conversation was created
    echo "Created conversation #{$result->id}";
} else {
    // Conversation already existed
    echo "Found existing conversation #{$result->id}";
}

// Create group conversation
$result = Conversation::create(
    $user, 
    'group', 
    [$userId1, $userId2, $userId3], 
    'Project Team'
);
```

**Response Properties:**
```php
$result->id;        // int - Conversation ID
$result->message;   // string - Status message
$result->existing;  // bool - Whether conversation already existed
```

---

#### `getUserConversations(Model $user): ConversationCollection`

Get all conversations for a user, with last message and unread count.

**Parameters:**
- `$user` (Model): The user to get conversations for

**Returns:** `ConversationCollection` value object

**Example:**
```php
$conversations = Conversation::getUserConversations($user);

// Get total count
echo "You have {$conversations->count()} conversations";

// Filter by type
$directChats = $conversations->filterByType('direct');
$groupChats = $conversations->filterByType('group');

// Iterate through conversations
foreach ($conversations->getConversations() as $conversation) {
    echo "{$conversation->title}: {$conversation->unreadCount} unread\n";
}

// Convert to array for API response
return response()->json($conversations->toArray());
```

---

#### `getOrCreateDirect(Model $user1, Model $user2): ?Conversation`

Get an existing direct conversation or create a new one between two users.

**Parameters:**
- `$user1` (Model): First user
- `$user2` (Model): Second user

**Returns:** `Conversation` model instance or null

**Example:**
```php
$conversation = Conversation::getOrCreateDirect($currentUser, $otherUser);

if ($conversation) {
    // Redirect to chat
    return redirect()->route('chat.show', $conversation->id);
}
```

**Note:** This method returns a Conversation model, not a value object. Use this when you need the Eloquent model for further operations.

---

#### `delete(int $conversationId, Model $user): void`

Delete a conversation. User must be a participant.

**Parameters:**
- `$conversationId` (int): The conversation ID to delete
- `$user` (Model): The user deleting the conversation

**Throws:** `Exception` if user is not a participant

**Example:**
```php
use Akira\LaravelChat\Facades\Conversation;

try {
    Conversation::delete($conversationId, $user);
    
    return response()->json([
        'message' => 'Conversation deleted successfully'
    ]);
} catch (Exception $e) {
    return response()->json([
        'error' => 'Cannot delete conversation'
    ], 403);
}
```

---

#### `validateParticipant(int $conversationId, Model $user): bool`

Check if a user is a participant in a conversation.

**Parameters:**
- `$conversationId` (int): The conversation ID
- `$user` (Model): The user to validate

**Returns:** `bool` - true if user is participant, false otherwise

**Example:**
```php
if (Conversation::validateParticipant($conversationId, $user)) {
    // User can access this conversation
    $messages = Message::getConversationMessages($user, $conversationId);
} else {
    abort(403, 'You are not a participant in this conversation');
}
```

---

## Message Facade

```php
use Akira\LaravelChat\Facades\Message;
```

### Methods

#### `send(Model $user, int $conversationId, string $content, string $type = 'text', ?array $metadata = null): MessageModel`

Send a message in a conversation.

**Parameters:**
- `$user` (Model): The user sending the message
- `$conversationId` (int): The conversation ID
- `$content` (string): The message content
- `$type` (string): Message type (`'text'`, `'image'`, `'file'`, `'audio'`, `'video'`)
- `$metadata` (array|null): Optional metadata (file URLs, sizes, etc.)

**Returns:** `Message` model instance

**Throws:** `Exception` if user is not a participant or policies forbid sending

**Example:**
```php
use Akira\LaravelChat\Facades\Message;

// Send text message
$message = Message::send($user, $conversationId, 'Hello!');

// Send image message
$message = Message::send($user, $conversationId, 'Check this out!', 'image', [
    'image_url' => 'https://example.com/photo.jpg',
    'thumbnail_url' => 'https://example.com/thumb.jpg',
    'width' => 1920,
    'height' => 1080,
]);

// Send file message
$message = Message::send($user, $conversationId, 'Document attached', 'file', [
    'file_url' => 'https://example.com/document.pdf',
    'file_name' => 'report.pdf',
    'file_size' => 2048576, // bytes
    'mime_type' => 'application/pdf',
]);
```

---

#### `getConversationMessages(Model $user, int $conversationId): MessageCollection`

Get all messages in a conversation for a user.

**Parameters:**
- `$user` (Model): The user requesting messages
- `$conversationId` (int): The conversation ID

**Returns:** `MessageCollection` value object

**Throws:** `Exception` if user is not a participant

**Example:**
```php
$messages = Message::getConversationMessages($user, $conversationId);

// Get total count
echo "Total messages: {$messages->count()}";

// Get unread messages
$unread = $messages->getUnread();
echo "Unread: {$unread->count()}";

// Filter by type
$images = $messages->filterByType('image');
$files = $messages->filterByType('file');

// Filter by user
$myMessages = $messages->filterByUser($user->id);

// Convert to array
return response()->json($messages->toArray());
```

---

#### `markAsRead(Model $user, int $conversationId, array $messageIds = []): void`

Mark messages as read by a user.

**Parameters:**
- `$user` (Model): The user marking messages as read
- `$conversationId` (int): The conversation ID
- `$messageIds` (array): Optional specific message IDs to mark as read (empty = mark all unread)

**Example:**
```php
// Mark all unread messages as read
Message::markAsRead($user, $conversationId);

// Mark specific messages as read
Message::markAsRead($user, $conversationId, [123, 124, 125]);
```

**Auto-marking:** You typically call this when a user opens a conversation or views specific messages.

---

#### `delete(int $messageId, Model $user): void`

Delete a message. User must be the sender.

**Parameters:**
- `$messageId` (int): The message ID to delete
- `$user` (Model): The user deleting the message

**Throws:** `Exception` if user is not the message sender

**Example:**
```php
try {
    Message::delete($messageId, $user);
    
    return response()->json([
        'message' => 'Message deleted successfully'
    ]);
} catch (Exception $e) {
    return response()->json([
        'error' => 'Cannot delete message'
    ], 403);
}
```

---

## Usage Examples

### Complete Chat Flow

```php
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;

// 1. Create or get conversation
$result = Conversation::create($user, 'direct', [$recipientId]);
$conversationId = $result->id;

// 2. Send a message
$message = Message::send($user, $conversationId, 'Hello!');

// 3. Get messages
$messages = Message::getConversationMessages($user, $conversationId);

// 4. Mark as read
Message::markAsRead($user, $conversationId);

// 5. Get updated conversations list
$conversations = Conversation::getUserConversations($user);
```

### API Controller Example

```php
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function conversations(Request $request)
    {
        $conversations = Conversation::getUserConversations($request->user());
        
        return response()->json($conversations);
    }
    
    public function messages(Request $request, int $conversationId)
    {
        // Validate participant
        if (!Conversation::validateParticipant($conversationId, $request->user())) {
            abort(403);
        }
        
        $messages = Message::getConversationMessages($request->user(), $conversationId);
        
        return response()->json($messages);
    }
    
    public function send(Request $request, int $conversationId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'type' => 'nullable|in:text,image,file,audio,video',
            'metadata' => 'nullable|array',
        ]);
        
        $message = Message::send(
            $request->user(),
            $conversationId,
            $validated['content'],
            $validated['type'] ?? 'text',
            $validated['metadata'] ?? null
        );
        
        return response()->json($message, 201);
    }
}
```

### Real-time Chat Component

```php
// Controller
public function show(Request $request, int $conversationId)
{
    if (!Conversation::validateParticipant($conversationId, $request->user())) {
        abort(403);
    }
    
    $messages = Message::getConversationMessages($request->user(), $conversationId);
    Message::markAsRead($request->user(), $conversationId);
    
    return view('chat.show', [
        'conversationId' => $conversationId,
        'messages' => $messages->toArray(),
    ]);
}
```

---

## Best Practices

### 1. Always Validate Participants

```php
// ✅ Good
if (Conversation::validateParticipant($conversationId, $user)) {
    // Proceed with operation
}

// ❌ Bad - No validation
$messages = Message::getConversationMessages($user, $conversationId);
```

### 2. Handle Exceptions

```php
// ✅ Good
try {
    Message::send($user, $conversationId, $content);
} catch (Exception $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}

// ❌ Bad - No error handling
Message::send($user, $conversationId, $content);
```

### 3. Use Value Objects

```php
// ✅ Good - Use value object methods
$conversations = Conversation::getUserConversations($user);
$directChats = $conversations->filterByType('direct');

// ❌ Bad - Converting to array too early
$conversations = Conversation::getUserConversations($user)->toArray();
// Lost all value object methods
```

### 4. Mark Messages as Read

```php
// ✅ Good - Mark as read when viewing
public function show($conversationId)
{
    $messages = Message::getConversationMessages($user, $conversationId);
    Message::markAsRead($user, $conversationId);
    
    return view('chat', compact('messages'));
}
```

---

## Navigation

← [Previous: configuration.md](configuration.md) | [Index](INDEX.md) | [Next: value-objects.md](value-objects.md) →

