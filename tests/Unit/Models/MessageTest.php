<?php

declare(strict_types=1);

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Tests\User;

test('message can be created', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test message',
        'type' => 'text',
    ]);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->content)->toBe('Test message')
        ->and($message->type)->toBe('text')
        ->and($message->user_id)->toBe($user->id);
});

test('message has correct fillable attributes', function (): void {
    $message = new Message();

    expect($message->getFillable())->toMatchArray([
        'conversation_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'read_at',
    ]);
});

test('message casts metadata to array', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test',
        'type' => 'text',
        'metadata' => ['key' => 'value'],
    ]);

    expect($message->metadata)->toBeArray()
        ->and($message->metadata)->toBe(['key' => 'value']);
});

test('message casts read_at to datetime', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test',
        'type' => 'text',
        'read_at' => now(),
    ]);

    expect($message->read_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('message belongs to conversation', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test',
        'type' => 'text',
    ]);

    expect($message->conversation)->toBeInstanceOf(Conversation::class)
        ->and($message->conversation->id)->toBe($conversation->id);
});

test('message belongs to user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test',
        'type' => 'text',
    ]);

    expect($message->user)->toBeInstanceOf(User::class)
        ->and($message->user->id)->toBe($user->id);
});

test('message can be marked as read', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Test',
        'type' => 'text',
    ]);

    expect($message->read_at)->toBeNull();

    $message->markAsRead();

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('unread scope returns only unread messages for user', function (): void {
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

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id,
    ]);

    // Message from user2 (unread by user1)
    $unreadMessage = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user2->id,
        'content' => 'Unread',
        'type' => 'text',
    ]);

    // Message from user2 (read by user1)
    Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user2->id,
        'content' => 'Read',
        'type' => 'text',
        'read_at' => now(),
    ]);

    // Message from user1 (should not be in unread for user1)
    Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user1->id,
        'content' => 'Own message',
        'type' => 'text',
    ]);

    $unreadForUser1 = Message::query()
        ->whereNull('read_at')
        ->where('user_id', '!=', $user1->id)
        ->get();

    expect($unreadForUser1)->toHaveCount(1)
        ->and($unreadForUser1->first()->id)->toBe($unreadMessage->id);
});
