<?php

declare(strict_types=1);

use Akira\LaravelChat\Actions\CreateConversationAction;
use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Models\Conversation;
use Akira\LaravelChat\Support\ValueObjects\CreateConversationResult;
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

test('create conversation action creates direct conversation', function (): void {
    $action = app(CreateConversationAction::class);

    $result = $action->handle($this->user1, 'direct', [$this->user2->id]);

    expect($result)->toBeInstanceOf(CreateConversationResult::class)
        ->and($result->existing)->toBeFalse()
        ->and($result->message)->toBe('Conversation created successfully');

    $conversation = Conversation::find($result->id);
    expect($conversation)->not->toBeNull()
        ->and($conversation->type)->toBe('direct')
        ->and($conversation->participants)->toHaveCount(2);
});

test('create conversation action creates group conversation', function (): void {
    $action = app(CreateConversationAction::class);

    $result = $action->handle($this->user1, 'group', [$this->user2->id, $this->user3->id], 'Test Group');

    expect($result)->toBeInstanceOf(CreateConversationResult::class)
        ->and($result->existing)->toBeFalse();

    $conversation = Conversation::find($result->id);
    expect($conversation)->not->toBeNull()
        ->and($conversation->type)->toBe('group')
        ->and($conversation->title)->toBe('Test Group')
        ->and($conversation->participants)->toHaveCount(3);
});

test('create conversation action returns existing direct conversation', function (): void {
    $action = app(CreateConversationAction::class);

    $result1 = $action->handle($this->user1, 'direct', [$this->user2->id]);

    expect($result1->existing)->toBeFalse();

    $result2 = $action->handle($this->user1, 'direct', [$this->user2->id]);

    expect($result2->existing)->toBeTrue()
        ->and($result2->id)->toBe($result1->id)
        ->and($result2->message)->toBe('Conversation already exists');
});

test('create conversation action throws exception for direct conversation with wrong participant count', function (): void {
    $action = app(CreateConversationAction::class);

    $action->handle($this->user1, 'direct', [$this->user2->id, $this->user3->id]);
})->throws(Exception::class, 'Direct conversations must have exactly one other participant');

test('create conversation action sets creator as admin', function (): void {
    $action = app(CreateConversationAction::class);

    $result = $action->handle($this->user1, 'group', [$this->user2->id], 'Test Group');

    $conversation = Conversation::find($result->id);
    $creatorParticipant = $conversation->participants()->where('user_id', $this->user1->id)->first();

    expect($creatorParticipant->pivot->is_admin)->toBe(1);
});

test('create conversation action filters out creator from participants', function (): void {
    $action = app(CreateConversationAction::class);

    // Pass creator's ID in participants array - should be filtered out
    $result = $action->handle($this->user1, 'direct', [$this->user1->id, $this->user2->id]);

    $conversation = Conversation::find($result->id);
    expect($conversation->participants)->toHaveCount(2);
});
