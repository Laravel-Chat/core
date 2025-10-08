<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class ConversationResult implements ValueObjectContract, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $participants
     * @param  array<string, mixed>|null  $lastMessage
     */
    public function __construct(
        public int $id,
        public string $type,
        public ?string $title,
        public string|int $createdBy,
        public array $participants,
        public ?array $lastMessage,
        public ?string $lastMessageAt,
        public int $unreadCount,
        public ?string $avatarUrl = null,
        public ?Model $otherParticipant = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'created_by' => $this->createdBy,
            'participants' => $this->participants,
            'last_message' => $this->lastMessage,
            'last_message_at' => $this->lastMessageAt,
            'unread_count' => $this->unreadCount,
            'avatar_url' => $this->avatarUrl,
            'other_participant' => $this->otherParticipant,
        ];
    }

    /**
     * @return Collection<string, mixed>
     */
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options) ?: '{}';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get only the essential data without the model instance.
     *
     * @return array<string, mixed>
     */
    public function toMinimalArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'created_by' => $this->createdBy,
            'participants' => $this->participants,
            'last_message' => $this->lastMessage,
            'last_message_at' => $this->lastMessageAt,
            'unread_count' => $this->unreadCount,
            'avatar_url' => $this->avatarUrl,
        ];
    }
}
