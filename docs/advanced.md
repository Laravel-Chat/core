# Advanced Usage

Advanced patterns and customization for power users.

## Table of Contents

- [Custom Models](#custom-models)
- [Custom Actions](#custom-actions)
- [Extending Facades](#extending-facades)
- [Custom Value Objects](#custom-value-objects)
- [Event Listeners](#event-listeners)
- [Database Customization](#database-customization)
- [Advanced Queries](#advanced-queries)
- [Caching Strategies](#caching-strategies)
- [Performance Optimization](#performance-optimization)

## Custom Models

### Extending Conversation Model

Add custom functionality to conversations:

```php
namespace App\Models;

use Akira\LaravelChat\Models\Conversation as BaseConversation;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends BaseConversation
{
    protected $fillable = [
        'type',
        'title',
        'created_by',
        'is_archived', // Custom field
        'archived_at', // Custom field
    ];

    // Custom method: Archive conversation
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    // Custom method: Unarchive conversation
    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    // Custom scope: Only active conversations
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    // Custom relationship: Pinned messages
    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('is_pinned', true)
            ->orderBy('created_at', 'desc');
    }

    // Custom accessor: Display name
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'direct') {
            $otherParticipant = $this->participants()
                ->where('user_id', '!=', auth()->id())
                ->first();
            
            return $otherParticipant?->name ?? 'Unknown User';
        }

        return $this->title ?? 'Group Chat';
    }
}
```

**Register in config:**

```php
// config/chat.php
'models' => [
    'conversation' => \App\Models\Conversation::class,
]
```

---

### Extending Message Model

Add custom message types and features:

```php
namespace App\Models;

use Akira\LaravelChat\Models\Message as BaseMessage;
use Illuminate\Support\Facades\Storage;

class Message extends BaseMessage
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'read_at',
        'is_pinned', // Custom field
        'reply_to_id', // Custom field - for message replies
        'is_edited', // Custom field
        'edited_at', // Custom field
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    // Custom relationship: Reply to message
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    // Custom relationship: Replies
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    // Custom method: Pin message
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    // Custom method: Unpin message
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    // Custom method: Edit message
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    // Custom accessor: File URL with signed URL
    public function getSecureFileUrlAttribute(): ?string
    {
        if ($this->type !== 'file' || !isset($this->metadata['file_path'])) {
            return null;
        }

        return Storage::temporaryUrl(
            $this->metadata['file_path'],
            now()->addMinutes(30)
        );
    }

    // Custom scope: Pinned messages
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
```

**Register in config:**

```php
// config/chat.php
'models' => [
    'message' => \App\Models\Message::class,
]
```

---

## Custom Actions

### Creating New Actions

Follow the action pattern for new functionality:

```php
namespace App\Actions\Chat;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Message;
use Illuminate\Database\Eloquent\Model;

class PinMessageAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    public function handle(Model $user, int $messageId): Message
    {
        $messageModel = $this->config->getMessageModel();
        $message = $messageModel::findOrFail($messageId);

        // Validate user is in conversation
        if (!$message->conversation->participants->contains($user)) {
            throw new \Exception('Not authorized');
        }

        $message->update(['is_pinned' => true]);

        return $message;
    }
}
```

### Using Custom Actions

```php
use App\Actions\Chat\PinMessageAction;

$action = app(PinMessageAction::class);
$pinnedMessage = $action->handle($user, $messageId);
```

---

## Extending Facades

### Creating Custom Facade Methods

Create a manager to expose custom functionality:

```php
namespace App\Support;

use App\Actions\Chat\PinMessageAction;
use App\Actions\Chat\ArchiveConversationAction;
use Illuminate\Database\Eloquent\Model;

class CustomChatManager
{
    public function __construct(
        private PinMessageAction $pinAction,
        private ArchiveConversationAction $archiveAction,
    ) {}

    public function pinMessage(Model $user, int $messageId): void
    {
        $this->pinAction->handle($user, $messageId);
    }

    public function archiveConversation(Model $user, int $conversationId): void
    {
        $this->archiveAction->handle($user, $conversationId);
    }
}
```

**Register in service provider:**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\CustomChatManager;

class ChatServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('chat.custom', function ($app) {
            return new CustomChatManager(
                $app->make(PinMessageAction::class),
                $app->make(ArchiveConversationAction::class),
            );
        });
    }
}
```

**Create facade:**

```php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void pinMessage(\Illuminate\Database\Eloquent\Model $user, int $messageId)
 * @method static void archiveConversation(\Illuminate\Database\Eloquent\Model $user, int $conversationId)
 */
class CustomChat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'chat.custom';
    }
}
```

**Usage:**

```php
use App\Facades\CustomChat;

CustomChat::pinMessage($user, $messageId);
CustomChat::archiveConversation($user, $conversationId);
```

---

## Custom Value Objects

Create custom value objects for specific use cases:

```php
namespace App\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

class ConversationStats implements ValueObjectContract, JsonSerializable
{
    public function __construct(
        public readonly int $conversationId,
        public readonly int $totalMessages,
        public readonly int $totalParticipants,
        public readonly int $unreadMessages,
        public readonly ?string $lastMessageAt,
        public readonly array $messageBreakdown, // by type
    ) {}

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'total_messages' => $this->totalMessages,
            'total_participants' => $this->totalParticipants,
            'unread_messages' => $this->unreadMessages,
            'last_message_at' => $this->lastMessageAt,
            'message_breakdown' => $this->messageBreakdown,
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

    public function hasActivity(): bool
    {
        return $this->totalMessages > 0;
    }

    public function hasUnread(): bool
    {
        return $this->unreadMessages > 0;
    }
}
```

---

## Event Listeners

### Custom Event Listeners

Listen to chat events for custom logic:

```php
namespace App\Listeners;

use Akira\LaravelChat\Events\MessageSent;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessageNotification;

class SendMessageNotification
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $conversation = $event->conversation;

        // Notify all participants except sender
        $participants = $conversation->participants()
            ->where('user_id', '!=', $message->user_id)
            ->get();

        foreach ($participants as $participant) {
            // Only notify if user has notifications enabled
            if ($participant->notify_messages ?? true) {
                Notification::send(
                    $participant,
                    new NewMessageNotification($message)
                );
            }
        }
    }
}
```

**Register in EventServiceProvider:**

```php
use Akira\LaravelChat\Events\MessageSent;
use App\Listeners\SendMessageNotification;

protected $listen = [
    MessageSent::class => [
        SendMessageNotification::class,
    ],
];
```

---

## Database Customization

### Adding Custom Columns

**Migration:**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->string('avatar')->nullable();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('reply_to_id')->nullable()
                ->constrained('messages')
                ->onDelete('set null');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'archived_at', 'avatar']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['is_pinned', 'reply_to_id', 'is_edited', 'edited_at']);
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'notifications_enabled', 'last_seen_at']);
        });
    }
};
```

---

## Advanced Queries

### Complex Conversation Queries

```php
use Akira\LaravelChat\Models\Conversation;
use Illuminate\Support\Facades\DB;

// Get conversations with unread count and last message
$conversations = Conversation::query()
    ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
    ->with([
        'participants',
        'messages' => fn($q) => $q->latest()->limit(1)
    ])
    ->withCount([
        'messages as unread_count' => function ($query) use ($user) {
            $query->whereNull('read_at')
                ->where('user_id', '!=', $user->id);
        }
    ])
    ->where('is_archived', false)
    ->orderByDesc(
        DB::raw('(SELECT created_at FROM messages WHERE conversation_id = conversations.id ORDER BY created_at DESC LIMIT 1)')
    )
    ->get();

// Get most active conversations
$activeConversations = Conversation::query()
    ->withCount('messages')
    ->having('messages_count', '>', 50)
    ->orderByDesc('messages_count')
    ->limit(10)
    ->get();

// Search messages
$results = Message::query()
    ->where('content', 'like', "%{$searchTerm}%")
    ->whereHas('conversation.participants', fn($q) => 
        $q->where('user_id', $user->id)
    )
    ->with(['conversation', 'user'])
    ->latest()
    ->paginate(20);
```

---

## Caching Strategies

### Cache Conversation Lists

```php
use Illuminate\Support\Facades\Cache;

class GetConversationsAction
{
    public function handle(Model $user): ConversationCollection
    {
        $cacheKey = "user.{$user->id}.conversations";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            // Expensive query
            return $this->fetchConversations($user);
        });
    }

    // Invalidate cache on new message
    public function invalidateCache(Model $user): void
    {
        Cache::forget("user.{$user->id}.conversations");
    }
}
```

### Cache Unread Counts

```php
use Illuminate\Support\Facades\Cache;

class UnreadCountService
{
    public function getUnreadCount(Model $user, int $conversationId): int
    {
        $cacheKey = "user.{$user->id}.conversation.{$conversationId}.unread";

        return Cache::remember($cacheKey, 60, function () use ($user, $conversationId) {
            return Message::query()
                ->where('conversation_id', $conversationId)
                ->whereNull('read_at')
                ->where('user_id', '!=', $user->id)
                ->count();
        });
    }

    public function incrementUnread(int $conversationId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $cacheKey = "user.{$userId}.conversation.{$conversationId}.unread";
            Cache::forget($cacheKey);
        }
    }
}
```

---

## Performance Optimization

### Eager Loading

```php
// ✅ Good - Eager load relationships
$conversations = Conversation::query()
    ->with([
        'participants:id,name,avatar',
        'messages' => fn($q) => $q->latest()->limit(1),
        'messages.user:id,name,avatar'
    ])
    ->get();

// ❌ Bad - N+1 queries
$conversations = Conversation::all();
foreach ($conversations as $conversation) {
    $lastMessage = $conversation->messages()->latest()->first(); // N+1
}
```

### Pagination

```php
// For large message lists
$messages = Message::query()
    ->where('conversation_id', $conversationId)
    ->with('user:id,name,avatar')
    ->latest()
    ->paginate(50);

// Cursor pagination for better performance
$messages = Message::query()
    ->where('conversation_id', $conversationId)
    ->with('user:id,name,avatar')
    ->latest()
    ->cursorPaginate(50);
```

### Database Indexes

```php
// Migration
Schema::table('messages', function (Blueprint $table) {
    $table->index(['conversation_id', 'created_at']);
    $table->index(['user_id', 'read_at']);
});

Schema::table('conversations', function (Blueprint $table) {
    $table->index(['type', 'created_at']);
});

Schema::table('conversation_participants', function (Blueprint $table) {
    $table->index(['user_id', 'conversation_id']);
});
```

---

## Navigation

← [Previous: actions.md](actions.md) | [Index](INDEX.md) | [Next: testing.md](testing.md) →

