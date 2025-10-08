<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property Carbon|null $joined_at
 * @property Carbon|null $last_read_at
 * @property bool $is_admin
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
        'last_read_at',
        'is_admin',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('chat.tables.conversation_participants', 'conversation_participants');
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
     * Update the last read timestamp for the participant.
     */
    public function updateLastRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }
}
