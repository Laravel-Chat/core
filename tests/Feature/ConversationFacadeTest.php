<?php

declare(strict_types=1);

use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Akira\LaravelChat\Support\ValueObjects\CreateConversationResult;
use Akira\LaravelChat\Support\ValueObjects\ConversationCollection;
use Akira\LaravelChat\Tests\User;

test('conversation facade can create direct conversation', function (): void {
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

    expect($result)->toBeInstanceOf(CreateConversationResult::class)
        ->and($result->id)->toBeInt()
        ->and($result->existing)->toBeFalse();

    $conversation = ConversationModel::find($result->id);
    expect($conversation)->not->toBeNull()
        ->and($conversation->type)->toBe('direct')
        ->and($conversation->participants)->toHaveCount(2);
});

test('conversation facade can create group conversation', function (): void {
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

    $result = Conversation::create($user1, 'group', [$user2->id, $user3->id], 'Team Chat');

    expect($result)->toBeInstanceOf(CreateConversationResult::class)
        ->and($result->id)->toBeInt();

    $conversation = ConversationModel::find($result->id);
    expect($conversation)->not->toBeNull()
        ->and($conversation->type)->toBe('group')
        ->and($conversation->title)->toBe('Team Chat')
        ->and($conversation->participants)->toHaveCount(3);
});

test('conversation facade can get user conversations', function (): void {
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

    Conversation::create($user1, 'direct', [$user2->id]);
    Conversation::create($user1, 'group', [$user2->id], 'Group 1');

    $conversations = Conversation::getUserConversations($user1);

    expect($conversations)->toBeInstanceOf(ConversationCollection::class)
        ->and($conversations->count())->toBe(2);
});

test('conversation facade returns existing direct conversation', function (): void {
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

    $result = Conversation::getOrCreateDirect($user1, $user2);

    expect($result)->toBeInstanceOf(ConversationModel::class);

    $conversation1Id = $result->id;

    // Try to get the same conversation again
    $result2 = Conversation::getOrCreateDirect($user1, $user2);

    expect($result2->id)->toBe($conversation1Id);
});

test('conversation facade can validate participant', function (): void {
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

    expect(Conversation::validateParticipant($conversation->id, $user1))->toBeTrue()
        ->and(Conversation::validateParticipant($conversation->id, $user2))->toBeTrue()
        ->and(Conversation::validateParticipant($conversation->id, $user3))->toBeFalse();
});

test('conversation facade can delete conversation', function (): void {
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
    expect($conversation)->not->toBeNull();

    Conversation::delete($conversation->id, $user1);

    expect(ConversationModel::find($conversation->id))->toBeNull();
});
