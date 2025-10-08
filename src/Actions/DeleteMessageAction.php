<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Models\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class DeleteMessageAction
{
    /**
     * Delete a message (only by its author).
     *
     * @throws ModelNotFoundException
     */
    public function handle(Model $user, int $messageId): void
    {
        /** @var Message $message */
        $message = Message::query()
            ->where('user_id', $user->getAttribute('id'))
            ->findOrFail($messageId);

        $message->delete();
    }
}
