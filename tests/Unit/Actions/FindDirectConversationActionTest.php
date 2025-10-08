<?php

declare(strict_types=1);

use Akira\LaravelChat\Actions\FindDirectConversationAction;
use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Tests\User;

test('find direct conversation action returns null when no conversation exists', function (): void {
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

    $action = app(FindDirectConversationAction::class);
    $result = $action->handle($user1, $user2);

    expect($result)->toBeNull();
});

test('find direct conversation action returns existing conversation', function (): void {
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

    // Create a direct conversation
    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id,
    ]);

    $conversation->participants()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
    ]);

    $action = app(FindDirectConversationAction::class);
    $result = $action->handle($user1, $user2);

    expect($result)->not->toBeNull()
        ->and($result)->toBeInstanceOf(Conversation::class)
        ->and($result->id)->toBe($conversation->id);
});

test('find direct conversation action works in both directions', function (): void {
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

    // Create a direct conversation
    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id,
    ]);

    $conversation->participants()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
    ]);

    $action = app(FindDirectConversationAction::class);
    
    // Test both directions
    $result1 = $action->handle($user1, $user2);
    $result2 = $action->handle($user2, $user1);

    expect($result1->id)->toBe($conversation->id)
        ->and($result2->id)->toBe($conversation->id);
});

test('find direct conversation action ignores group conversations', function (): void {
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

    // Create a group conversation (not direct)
    $conversation = Conversation::create([
        'type' => 'group',
        'created_by' => $user1->id,
    ]);

    $conversation->participants()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
    ]);

    $action = app(FindDirectConversationAction::class);
    $result = $action->handle($user1, $user2);

    // Should not find the group conversation
    expect($result)->toBeNull();
});

test('find direct conversation action requires exactly 2 participants', function (): void {
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

    // Create a direct conversation with 3 participants (invalid)
    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id,
    ]);

    $conversation->participants()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
        $user3->id => ['joined_at' => now()],
    ]);

    $action = app(FindDirectConversationAction::class);
    $result = $action->handle($user1, $user2);

    // Should not find conversation with more than 2 participants
    expect($result)->toBeNull();
});
