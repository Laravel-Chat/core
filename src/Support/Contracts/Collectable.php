<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\Contracts;

use Illuminate\Support\Collection;

interface Collectable
{
    /**
     * Convert the value object to a collection.
     *
     * @return Collection<string, mixed>
     */
    public function toCollection(): Collection;
}
