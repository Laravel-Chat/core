<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class ConversationCollection implements ValueObjectContract, JsonSerializable
{
    /**
     * @param  Collection<int, ConversationResult>  $conversations
     */
    public function __construct(
        private Collection $conversations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'conversations' => $this->conversations->map(
                fn (ConversationResult $conversation): array => $conversation->toArray()
            )->values()->toArray(),
            'total' => $this->conversations->count(),
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
     * Get minimal array representation.
     *
     * @return array<string, mixed>
     */
    public function toMinimalArray(): array
    {
        return [
            'conversations' => $this->conversations->map(
                fn (ConversationResult $conversation): array => $conversation->toMinimalArray()
            )->values()->toArray(),
            'total' => $this->conversations->count(),
        ];
    }

    /**
     * Get the conversations collection.
     *
     * @return Collection<int, ConversationResult>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    /**
     * Get total count.
     */
    public function count(): int
    {
        return $this->conversations->count();
    }

    /**
     * Check if collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->conversations->isEmpty();
    }

    /**
     * Filter conversations by type.
     *
     * @return Collection<int, ConversationResult>
     */
    public function filterByType(string $type): Collection
    {
        return $this->conversations->filter(
            fn (ConversationResult $conversation): bool => $conversation->type === $type
        );
    }
}
