<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\Contracts;

interface Arrayable
{
    /**
     * Convert the value object to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
