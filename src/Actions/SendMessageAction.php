<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Events\MessageSent;
use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class SendMessageAction
{
    public function __construct(
        private ChatConfig $config
    ) {}

    public function handle(
        Model $user,
        int $conversationId,
        string $content,
        string $type = 'text',
        ?array $metadata = null
    ): Message {
        $conversation = $this->findConversationForUser($user, $conversationId);

        return DB::transaction(function () use ($user, $conversation, $content, $type, $metadata): Message {
            $messageModel = $this->config->getMessageModel();
            
            /** @var Message $message */
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $content,
                'type' => $type,
                'metadata' => $metadata,
            ]);

            $conversation->update(['last_message_at' => now()]);

            if ($this->config->isBroadcastingEnabled()) {
                MessageSent::dispatch($message);
            }

            $message->load('user');

            return $message;
        });
    }

    private function findConversationForUser(Model $user, int $conversationId): Conversation
    {
        $conversationModel = $this->config->getConversationModel();

        $conversation = $conversationModel::query()
            ->whereHas('participants', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id);
            })
            ->find($conversationId);

        if (! $conversation instanceof Conversation) {
            throw new ModelNotFoundException('Conversation not found or user is not a participant');
        }

        return $conversation;
    }
}
