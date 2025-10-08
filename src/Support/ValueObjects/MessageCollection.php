<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class MessageCollection implements ValueObjectContract, JsonSerializable
{
    /**
     * @param  Collection<int, MessageResult>  $messages
     */
    public function __construct(
        private Collection $messages,
        public ?int $conversationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messages' => $this->messages->map(
                fn (MessageResult $message): array => $message->toArray()
            )->values()->toArray(),
            'total' => $this->messages->count(),
            'conversation_id' => $this->conversationId,
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
     * Get the messages collection.
     *
     * @return Collection<int, MessageResult>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * Get total count.
     */
    public function count(): int
    {
        return $this->messages->count();
    }

    /**
     * Check if collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->messages->isEmpty();
    }

    /**
     * Get unread messages.
     *
     * @return Collection<int, MessageResult>
     */
    public function getUnread(): Collection
    {
        return $this->messages->filter(
            fn (MessageResult $message): bool => ! $message->isRead()
        );
    }

    /**
     * Get messages by type.
     *
     * @return Collection<int, MessageResult>
     */
    public function filterByType(string $type): Collection
    {
        return $this->messages->filter(
            fn (MessageResult $message): bool => $message->isType($type)
        );
    }

    /**
     * Get messages by user.
     *
     * @return Collection<int, MessageResult>
     */
    public function filterByUser(int|string $userId): Collection
    {
        return $this->messages->filter(
            fn (MessageResult $message): bool => $message->userId === $userId
        );
    }
}
