<?php

declare(strict_types=1);

use Akira\LaravelChat\Actions\GetConversationsAction;
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Akira\LaravelChat\Support\ValueObjects\ConversationCollection;
use Akira\LaravelChat\Tests\User;

beforeEach(function (): void {
    $this->user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $this->user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $this->user3 = User::create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);
});

test('get conversations action returns conversation collection', function (): void {
    Conversation::create($this->user1, 'direct', [$this->user2->id]);
    Conversation::create($this->user1, 'group', [$this->user2->id, $this->user3->id], 'Test Group');

    $action = app(GetConversationsAction::class);

    $result = $action->handle($this->user1);

    expect($result)->toBeInstanceOf(ConversationCollection::class)
        ->and($result->count())->toBe(2);
});

test('get conversations action returns empty collection when user has no conversations', function (): void {
    $action = app(GetConversationsAction::class);

    $result = $action->handle($this->user1);

    expect($result)->toBeInstanceOf(ConversationCollection::class)
        ->and($result->isEmpty())->toBeTrue();
});

test('get conversations action includes unread count', function (): void {
    $result = Conversation::create($this->user1, 'direct', [$this->user2->id]);
    $conversation = ConversationModel::find($result->id);

    Message::send($this->user2, $conversation->id, 'Unread message 1');
    Message::send($this->user2, $conversation->id, 'Unread message 2');

    $action = app(GetConversationsAction::class);

    $conversations = $action->handle($this->user1);

    $firstConversation = $conversations->getConversations()->first();
    expect($firstConversation->unreadCount)->toBe(2);
});

test('get conversations action includes last message', function (): void {
    $result = Conversation::create($this->user1, 'direct', [$this->user2->id]);
    $conversation = ConversationModel::find($result->id);

    Message::send($this->user1, $conversation->id, 'First message');
    $lastMessage = Message::send($this->user2, $conversation->id, 'Last message');

    $action = app(GetConversationsAction::class);

    $conversations = $action->handle($this->user1);

    $firstConversation = $conversations->getConversations()->first();
    expect($firstConversation->lastMessage)->toBeArray()
        ->and($firstConversation->lastMessage['content'])->toBe('Last message');
});

test('get conversations action orders by last message date', function (): void {
    $result1 = Conversation::create($this->user1, 'direct', [$this->user2->id]);
    $conversation1 = ConversationModel::find($result1->id);

    $result2 = Conversation::create($this->user1, 'direct', [$this->user3->id]);
    $conversation2 = ConversationModel::find($result2->id);

    Message::send($this->user1, $conversation1->id, 'Message in conversation 1');
    sleep(1);
    Message::send($this->user1, $conversation2->id, 'Message in conversation 2');

    $action = app(GetConversationsAction::class);

    $conversations = $action->handle($this->user1);

    $conversationsList = $conversations->getConversations();
    expect($conversationsList->first()->id)->toBe($conversation2->id);
});

test('get conversations action filters by conversation type', function (): void {
    Conversation::create($this->user1, 'direct', [$this->user2->id]);
    Conversation::create($this->user1, 'group', [$this->user2->id, $this->user3->id], 'Test Group');

    $action = app(GetConversationsAction::class);

    $conversations = $action->handle($this->user1);

    $directConversations = $conversations->filterByType('direct');
    $groupConversations = $conversations->filterByType('group');

    expect($directConversations->count())->toBe(1)
        ->and($groupConversations->count())->toBe(1);
});
