<?php

declare(strict_types=1);

use Akira\LaravelChat\Actions\GetConversationMessagesAction;
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Akira\LaravelChat\Support\ValueObjects\MessageCollection;
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

    $result = Conversation::create($this->user1, 'direct', [$this->user2->id]);
    $this->conversation = ConversationModel::find($result->id);
});

test('get conversation messages action returns message collection', function (): void {
    Message::send($this->user1, $this->conversation->id, 'Message 1');
    Message::send($this->user2, $this->conversation->id, 'Message 2');
    Message::send($this->user1, $this->conversation->id, 'Message 3');

    $action = app(GetConversationMessagesAction::class);

    $result = $action->handle($this->user1, $this->conversation->id);

    expect($result)->toBeInstanceOf(MessageCollection::class)
        ->and($result->count())->toBe(3)
        ->and($result->conversationId)->toBe($this->conversation->id);
});

test('get conversation messages action returns empty collection when no messages', function (): void {
    $action = app(GetConversationMessagesAction::class);

    $result = $action->handle($this->user1, $this->conversation->id);

    expect($result)->toBeInstanceOf(MessageCollection::class)
        ->and($result->isEmpty())->toBeTrue();
});

test('get conversation messages action orders messages by creation date', function (): void {
    $message1 = Message::send($this->user1, $this->conversation->id, 'First');
    sleep(1);
    $message2 = Message::send($this->user2, $this->conversation->id, 'Second');
    sleep(1);
    $message3 = Message::send($this->user1, $this->conversation->id, 'Third');

    $action = app(GetConversationMessagesAction::class);

    $result = $action->handle($this->user1, $this->conversation->id);

    $messages = $result->getMessages();
    expect($messages->first()->id)->toBe($message1->id)
        ->and($messages->last()->id)->toBe($message3->id);
});

test('get conversation messages action throws exception when user is not participant', function (): void {
    $user3 = User::create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);

    $action = app(GetConversationMessagesAction::class);

    $action->handle($user3, $this->conversation->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('get conversation messages action includes message metadata', function (): void {
    $metadata = ['key' => 'value'];
    Message::send($this->user1, $this->conversation->id, 'Test', 'text', $metadata);

    $action = app(GetConversationMessagesAction::class);

    $result = $action->handle($this->user1, $this->conversation->id);

    $messages = $result->getMessages();
    expect($messages->first()->metadata)->toBe($metadata);
});
