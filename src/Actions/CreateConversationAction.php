<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Actions;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Events\ConversationCreated;
use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Support\ValueObjects\CreateConversationResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateConversationAction
{
    public function __construct(
        private ChatConfig $config,
        private FindDirectConversationAction $findDirectAction
    ) {}

    /**
     * Create a new conversation.
     *
     * @param  array<int>  $participantIds
     *
     * @throws Throwable
     */
    public function handle(Model $creator, string $type, array $participantIds, ?string $title = null): CreateConversationResult
    {

        /** @var SupportCollection<int, int> $filteredParticipantIds */
        $filteredParticipantIds = collect($participantIds)
            ->filter(fn (int $id): bool => $id !== $creator->getAttribute('id'))
            ->values();

        if ($type === 'direct' && $filteredParticipantIds->count() !== 1) {
            throw new Exception('Direct conversations must have exactly one other participant');
        }

        if ($type === 'direct') {
            $userModel = $this->config->getUserModel();
            /** @var Model|null $otherUser */
            $otherUser = $userModel::query()->find($filteredParticipantIds->first());
            if (! $otherUser instanceof Model) {
                throw new Exception('Invalid participant');
            }

            // Check if the other user accepts messages from the creator using policy
            if (method_exists($otherUser, 'canReceiveMessagesFrom') && ! $otherUser->canReceiveMessagesFrom($creator)) {
                throw new Exception('Este utilizador não aceita mensagens.');
            }

            /** @var Conversation|null $existingConversation */
            $existingConversation = $this->findDirectAction->handle($creator, $otherUser);
            if ($existingConversation instanceof Conversation) {
                return new CreateConversationResult(
                    id: (int) $existingConversation->getAttribute('id'),
                    message: 'Conversation already exists',
                    existing: true
                );
            }
        }

        /** @var CreateConversationResult $transactionResult */
        $transactionResult = DB::transaction(function () use ($creator, $type, $title, $filteredParticipantIds): CreateConversationResult {
            $conversationModel = $this->config->getConversationModel();
            /** @var Conversation $conversation */
            $conversation = $conversationModel::query()->create([
                'title' => $title,
                'type' => $type,
                'created_by' => $creator->getAttribute('id'),
            ]);

            $this->attachParticipants($conversation, $creator, $filteredParticipantIds);

            if ($this->config->isBroadcastingEnabled()) {
                $this->broadcastConversationCreated($conversation);
            }

            return new CreateConversationResult(
                id: (int) $conversation->getAttribute('id'),
                message: 'Conversation created successfully',
                existing: false
            );
        });

        return $transactionResult;
    }

    /**
     * Attach participants to conversation.
     *
     * @param  SupportCollection<int, int>  $participantIds
     */
    private function attachParticipants(Conversation $conversation, Model $creator, SupportCollection $participantIds): void
    {
        /** @var SupportCollection<int, mixed> $allParticipants */
        $allParticipants = $participantIds->concat([$creator->getAttribute('id')]);

        /** @var array<int, array{joined_at: Carbon, is_admin: bool}> $attachData */
        $attachData = [];
        /** @var int $creatorId */
        $creatorId = (int) $creator->getAttribute('id');

        /** @var mixed $id */
        foreach ($allParticipants as $id) {
            if (is_numeric($id)) {
                $intId = (int) $id;
                $attachData[$intId] = [
                    'joined_at' => now(),
                    'is_admin' => $intId === $creatorId,
                ];
            }
        }

        $conversation->participants()->attach($attachData);
    }

    /**
     * Broadcast conversation created event to all participants.
     */
    private function broadcastConversationCreated(Conversation $conversation): void
    {
        $conversation->load('participants');

        /** @var Collection<int, Model> $participants */
        $participants = $conversation->getRelation('participants');

        foreach ($participants as $participant) {
            ConversationCreated::dispatch($conversation, $participant);
        }
    }
}
