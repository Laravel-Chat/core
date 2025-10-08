<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class MessageResult implements ValueObjectContract, JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $user
     */
    public function __construct(
        public int $id,
        public int $conversationId,
        public string|int $userId,
        public string $content,
        public string $type,
        public ?array $metadata,
        public ?string $readAt,
        public string $createdAt,
        public ?array $user = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'content' => $this->content,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
            'user' => $this->user,
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
     * Check if the message has been read.
     */
    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    /**
     * Check if the message is of a specific type.
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }
}
