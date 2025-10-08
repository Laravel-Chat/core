<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $title
 * @property string $type
 * @property int $created_by
 * @property Carbon|null $last_message_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'created_by',
        'last_message_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Use ChatConfig for table name
        try {
            $config = \Akira\LaravelChat\Config\ChatConfig::getInstance();
            $tableName = $config->getConversationsTable();
            assert(is_string($tableName));
            $this->table = $tableName;
        } catch (\Throwable) {
            // Fallback for when config is not available (e.g., during migrations)
            $tableName = config('chat.tables.conversations', 'conversations');
            assert(is_string($tableName) || $tableName === null);
            $this->table = $tableName;
        }
    }

    /**
     * Scope a query to only include conversations for a given user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    public function forUser(Builder $query, Model $user): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($user): void {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Scope a query to direct conversation between two users.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    public function directConversation(Builder $query, Model $user1, Model $user2): Builder
    {
        return $query->where('type', 'direct')
            ->whereHas('participants', function (Builder $q) use ($user1): void {
                $q->where('user_id', $user1->id);
            })
            ->whereHas('participants', function (Builder $q) use ($user2): void {
                $q->where('user_id', $user2->id);
            })
            ->has('participants', '=', 2);
    }

    /**
     * Get the creator of the conversation.
     *
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        $userModel = config('chat.user_model', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'created_by');
    }

    /**
     * Get the messages for the conversation.
     *
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        $messageModel = config('chat.models.message', Message::class);

        return $this->hasMany($messageModel);
    }

    /**
     * Get the participants of the conversation.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function participants(): BelongsToMany
    {
        $userModel = config('chat.user_model', 'App\\Models\\User');
        $pivotTable = config('chat.tables.conversation_participants', 'conversation_participants');

        return $this->belongsToMany($userModel, $pivotTable)
            ->withPivot(['joined_at', 'last_read_at', 'is_admin'])
            ->withTimestamps();
    }

    /**
     * Get the latest message for the conversation.
     *
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        $messageModel = config('chat.models.message', Message::class);

        return $this->hasOne($messageModel)->latestOfMany();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }
}
