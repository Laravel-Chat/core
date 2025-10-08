<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Support\Contracts;

interface Jsonable
{
    /**
     * Convert the value object to JSON.
     */
    public function toJson(int $options = 0): string;
}
