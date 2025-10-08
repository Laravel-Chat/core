<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Illuminate\Database\Eloquent\Model;

final readonly class GetAvatarAction
{
    /**
     * Get avatar URL for a user.
     */
    public function handle(?Model $user): ?string
    {
        if (!$user instanceof \Illuminate\Database\Eloquent\Model) {
            return null;
        }

        // Return null as default, application can implement custom logic
        return $user->avatar_url ?? $user->avatar ?? null;
    }
}
