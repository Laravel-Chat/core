<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property string $content
 * @property string $type
 * @property array<string,mixed>|null $metadata
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'read_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('chat.tables.messages', 'messages');
    }

    /**
     * Conversation relationship
     *
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        $conversationModel = config('chat.models.conversation', Conversation::class);

        return $this->belongsTo($conversationModel);
    }

    /**
     * User relationship
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        $userModel = config('chat.user_model', 'App\\Models\\User');

        return $this->belongsTo($userModel);
    }

    /**
     * Scope a query to only include unread messages for a given user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    public function unread(Builder $query, Model $user): Builder
    {
        /** @var Builder<self> $result */
        $result = $query->whereNull('read_at')
            ->where('user_id', '!=', $user->getAttribute('id'));

        return $result;
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'read_at' => 'datetime',
        ];
    }
}
