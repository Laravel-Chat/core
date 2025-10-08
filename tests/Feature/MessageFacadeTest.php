<?php

declare(strict_types=1);

use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Facades\Message;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Akira\LaravelChat\Models\Message as MessageModel;
use Akira\LaravelChat\Tests\User;

test('message facade can send message', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    $message = Message::send($user1, $conversation->id, 'Hello World');

    expect($message)->toBeInstanceOf(MessageModel::class)
        ->and($message->content)->toBe('Hello World')
        ->and($message->user_id)->toBe($user1->id)
        ->and($message->conversation_id)->toBe($conversation->id);
});

test('message facade can send message with metadata', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    $metadata = ['file_url' => 'https://example.com/file.pdf', 'file_size' => 1024];
    $message = Message::send($user1, $conversation->id, 'Check this file', 'file', $metadata);

    expect($message->type)->toBe('file')
        ->and($message->metadata)->toBe($metadata);
});

test('message facade updates conversation last_message_at when sending', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    $previousLastMessageAt = $conversation->last_message_at;

    sleep(1);

    Message::send($user1, $conversation->id, 'Hello');

    $conversation->refresh();

    expect($conversation->last_message_at)->not->toBeNull()
        ->and($conversation->last_message_at > $previousLastMessageAt)->toBeTrue();
});

test('message facade can get conversation messages', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    Message::send($user1, $conversation->id, 'Message 1');
    Message::send($user2, $conversation->id, 'Message 2');
    Message::send($user1, $conversation->id, 'Message 3');

    $messages = Message::getConversationMessages($user1, $conversation->id);

    expect($messages->toArray())->toBeArray()
        ->and($messages->toArray())->toHaveKey('messages')
        ->and($messages->toArray()['messages'])->toHaveCount(3);
});

test('message facade can mark messages as read', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    $message = Message::send($user2, $conversation->id, 'Unread message');

    expect($message->read_at)->toBeNull();

    Message::markAsRead($user1, $conversation->id);

    $message->refresh();
    expect($message->read_at)->not->toBeNull();
});

test('message facade can delete message', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    $message = Message::send($user1, $conversation->id, 'To be deleted');

    expect(MessageModel::find($message->id))->not->toBeNull();

    Message::delete($message->id, $user1);

    expect(MessageModel::find($message->id))->toBeNull();
});

test('message facade throws exception when user is not participant', function (): void {
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $user2 = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $user3 = User::create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);

    $result = Conversation::create($user1, 'direct', [$user2->id]);
    $conversation = ConversationModel::find($result->id);

    Message::send($user3, $conversation->id, 'Should fail');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
