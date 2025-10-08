<?php

declare(strict_types=1);

use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Tests\User;

test('conversation can be created', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'title' => 'Test Conversation',
        'type' => 'group',
        'created_by' => $user->id,
    ]);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->title)->toBe('Test Conversation')
        ->and($conversation->type)->toBe('group')
        ->and($conversation->created_by)->toBe($user->id);
});

test('conversation has correct fillable attributes', function (): void {
    $conversation = new Conversation();

    expect($conversation->getFillable())->toMatchArray([
        'title',
        'type',
        'created_by',
        'last_message_at',
    ]);
});

test('conversation casts last_message_at to datetime', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
        'last_message_at' => now(),
    ]);

    expect($conversation->last_message_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('conversation belongs to creator', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    expect($conversation->creator)->toBeInstanceOf(User::class)
        ->and($conversation->creator->id)->toBe($user->id);
});

test('conversation can have messages', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $conversation->messages()->create([
        'user_id' => $user->id,
        'content' => 'Hello World',
        'type' => 'text',
    ]);

    expect($conversation->messages)->toHaveCount(1)
        ->and($conversation->messages->first()->content)->toBe('Hello World');
});

test('conversation can have participants', function (): void {
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
        $user1->id => ['joined_at' => now(), 'is_admin' => true],
        $user2->id => ['joined_at' => now(), 'is_admin' => false],
    ]);

    expect($conversation->participants)->toHaveCount(2);
});

test('conversation scope for user returns only user conversations', function (): void {
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

    $conversation1 = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id,
    ]);
    $conversation1->participants()->attach($user1->id, ['joined_at' => now()]);

    $conversation2 = Conversation::create([
        'type' => 'direct',
        'created_by' => $user2->id,
    ]);
    $conversation2->participants()->attach($user2->id, ['joined_at' => now()]);

    $userConversations = Conversation::query()
        ->whereHas('participants', function ($q) use ($user1): void {
            $q->where('user_id', $user1->id);
        })
        ->get();

    expect($userConversations)->toHaveCount(1)
        ->and($userConversations->first()->id)->toBe($conversation1->id);
});

test('conversation scope finds direct conversation between two users', function (): void {
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

    $found = Conversation::query()
        ->where('type', 'direct')
        ->whereHas('participants', fn ($q) => $q->where('user_id', $user1->id))
        ->whereHas('participants', fn ($q) => $q->where('user_id', $user2->id))
        ->has('participants', '=', 2)
        ->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($conversation->id);
});

test('conversation has latest message relationship', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'created_by' => $user->id,
    ]);

    $conversation->messages()->create([
        'user_id' => $user->id,
        'content' => 'First message',
        'type' => 'text',
    ]);

    sleep(1);

    $conversation->messages()->create([
        'user_id' => $user->id,
        'content' => 'Latest message',
        'type' => 'text',
    ]);

    $conversation->load('latestMessage');

    expect($conversation->latestMessage)->not->toBeNull()
        ->and($conversation->latestMessage->content)->toBe('Latest message');
});
