<?php

declare(strict_types=1);

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Tests\User;

test('user has conversations relationship', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $conversation->participants()->attach($user->id, [
        'joined_at' => now(),
        'is_admin' => true,
    ]);

    expect($user->conversations)->toHaveCount(1)
        ->and($user->conversations->first()->id)->toBe($conversation->id);
});

test('user can get unread messages count', function (): void {
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

    $conversation->participants()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
    ]);

    // Create unread messages from user2
    $conversation->messages()->create([
        'user_id' => $user2->id,
        'content' => 'Message 1',
        'type' => 'text',
    ]);

    $conversation->messages()->create([
        'user_id' => $user2->id,
        'content' => 'Message 2',
        'type' => 'text',
    ]);

    // Create read message from user2
    $conversation->messages()->create([
        'user_id' => $user2->id,
        'content' => 'Read message',
        'type' => 'text',
        'read_at' => now(),
    ]);

    // Create message from user1 (should not count)
    $conversation->messages()->create([
        'user_id' => $user1->id,
        'content' => 'Own message',
        'type' => 'text',
    ]);

    expect($user1->unreadMessagesCount())->toBe(2);
});

test('conversations are ordered by last_message_at', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation1 = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
        'last_message_at' => now()->subDays(2),
    ]);

    $conversation2 = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
        'last_message_at' => now()->subDay(),
    ]);

    $conversation3 = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
        'last_message_at' => now(),
    ]);

    $conversation1->participants()->attach($user->id, ['joined_at' => now()]);
    $conversation2->participants()->attach($user->id, ['joined_at' => now()]);
    $conversation3->participants()->attach($user->id, ['joined_at' => now()]);

    $conversations = $user->conversations;

    expect($conversations)->toHaveCount(3)
        ->and($conversations[0]->id)->toBe($conversation3->id)
        ->and($conversations[1]->id)->toBe($conversation2->id)
        ->and($conversations[2]->id)->toBe($conversation1->id);
});
