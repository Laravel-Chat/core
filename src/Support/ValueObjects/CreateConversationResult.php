<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\ValueObjects;

use Akira\LaravelChat\Support\Contracts\ValueObjectContract;
use Illuminate\Support\Collection;
use JsonSerializable;

final readonly class CreateConversationResult implements ValueObjectContract, JsonSerializable
{
    public function __construct(
        public int $id,
        public string $message,
        public bool $existing,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'existing' => $this->existing,
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
     * Check if the conversation already existed.
     */
    public function wasExisting(): bool
    {
        return $this->existing;
    }

    /**
     * Check if the conversation was newly created.
     */
    public function wasCreated(): bool
    {
        return ! $this->existing;
    }
}
