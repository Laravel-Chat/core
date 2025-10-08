<?php

declare(strict_types=1);

use Akira\LaravelChat\Actions\SendMessageAction;
use Akira\LaravelChat\Facades\Conversation;
use Akira\LaravelChat\Models\Conversation as ConversationModel;
use Akira\LaravelChat\Models\Message;
use Akira\LaravelChat\Tests\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

test('send message action creates message', function (): void {
    $action = app(SendMessageAction::class);

    $message = $action->handle($this->user1, $this->conversation->id, 'Hello World');

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->content)->toBe('Hello World')
        ->and($message->user_id)->toBe($this->user1->id)
        ->and($message->conversation_id)->toBe($this->conversation->id)
        ->and($message->type)->toBe('text');
});

test('send message action creates message with metadata', function (): void {
    $action = app(SendMessageAction::class);

    $metadata = ['file_url' => 'https://example.com/file.pdf', 'file_size' => 1024];
    $message = $action->handle($this->user1, $this->conversation->id, 'File attachment', 'file', $metadata);

    expect($message->type)->toBe('file')
        ->and($message->metadata)->toBe($metadata);
});

test('send message action updates conversation last_message_at', function (): void {
    $action = app(SendMessageAction::class);

    $previousLastMessageAt = $this->conversation->last_message_at;

    sleep(1);

    $action->handle($this->user1, $this->conversation->id, 'Test');

    $this->conversation->refresh();

    expect($this->conversation->last_message_at)->not->toBeNull()
        ->and($this->conversation->last_message_at > $previousLastMessageAt)->toBeTrue();
});

test('send message action throws exception when user is not participant', function (): void {
    $user3 = User::create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);

    $action = app(SendMessageAction::class);

    $action->handle($user3, $this->conversation->id, 'Should fail');
})->throws(ModelNotFoundException::class);

test('send message action creates message with different types', function (): void {
    $action = app(SendMessageAction::class);

    $types = ['text', 'file', 'image', 'video', 'audio'];

    foreach ($types as $type) {
        $message = $action->handle($this->user1, $this->conversation->id, 'Content', $type);
        expect($message->type)->toBe($type);
    }
});
