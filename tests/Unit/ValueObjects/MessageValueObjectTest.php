<?php

declare(strict_types=1);

use Akira\LaravelChat\Support\ValueObjects\MessageResult;
use Akira\LaravelChat\Support\ValueObjects\MessageCollection;
use Illuminate\Support\Collection;

test('message result can be created', function (): void {
    $messageResult = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: ['key' => 'value'],
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    expect($messageResult->id)->toBe(1)
        ->and($messageResult->conversationId)->toBe(10)
        ->and($messageResult->userId)->toBe(5)
        ->and($messageResult->content)->toBe('Hello World')
        ->and($messageResult->type)->toBe('text');
});

test('message result can convert to array', function (): void {
    $messageResult = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: null,
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    $array = $messageResult->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('id')
        ->and($array['id'])->toBe(1)
        ->and($array['content'])->toBe('Hello World');
});

test('message result can convert to collection', function (): void {
    $messageResult = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: null,
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    $collection = $messageResult->toCollection();

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->get('id'))->toBe(1)
        ->and($collection->get('content'))->toBe('Hello World');
});

test('message result can convert to json', function (): void {
    $messageResult = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: null,
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    $json = $messageResult->toJson();

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toBeArray()
        ->and(json_decode($json, true)['id'])->toBe(1);
});

test('message result can check if read', function (): void {
    $unreadMessage = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: null,
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    $readMessage = new MessageResult(
        id: 2,
        conversationId: 10,
        userId: 5,
        content: 'Hello World',
        type: 'text',
        metadata: null,
        readAt: '2024-01-01 13:00:00',
        createdAt: '2024-01-01 12:00:00'
    );

    expect($unreadMessage->isRead())->toBeFalse()
        ->and($readMessage->isRead())->toBeTrue();
});

test('message result can check type', function (): void {
    $messageResult = new MessageResult(
        id: 1,
        conversationId: 10,
        userId: 5,
        content: 'File.pdf',
        type: 'file',
        metadata: null,
        readAt: null,
        createdAt: '2024-01-01 12:00:00'
    );

    expect($messageResult->isType('file'))->toBeTrue()
        ->and($messageResult->isType('text'))->toBeFalse();
});

test('message collection can be created', function (): void {
    $messages = collect([
        new MessageResult(1, 10, 5, 'Message 1', 'text', null, null, '2024-01-01 12:00:00'),
        new MessageResult(2, 10, 6, 'Message 2', 'text', null, null, '2024-01-01 12:01:00'),
    ]);

    $collection = new MessageCollection($messages, 10);

    expect($collection->count())->toBe(2)
        ->and($collection->conversationId)->toBe(10);
});

test('message collection can convert to array', function (): void {
    $messages = collect([
        new MessageResult(1, 10, 5, 'Message 1', 'text', null, null, '2024-01-01 12:00:00'),
    ]);

    $collection = new MessageCollection($messages, 10);
    $array = $collection->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('messages')
        ->and($array)->toHaveKey('total')
        ->and($array['total'])->toBe(1);
});

test('message collection can filter unread messages', function (): void {
    $messages = collect([
        new MessageResult(1, 10, 5, 'Message 1', 'text', null, null, '2024-01-01 12:00:00'),
        new MessageResult(2, 10, 6, 'Message 2', 'text', null, '2024-01-01 13:00:00', '2024-01-01 12:01:00'),
    ]);

    $collection = new MessageCollection($messages);
    $unread = $collection->getUnread();

    expect($unread->count())->toBe(1)
        ->and($unread->first()->id)->toBe(1);
});

test('message collection can filter by type', function (): void {
    $messages = collect([
        new MessageResult(1, 10, 5, 'Text message', 'text', null, null, '2024-01-01 12:00:00'),
        new MessageResult(2, 10, 6, 'File.pdf', 'file', null, null, '2024-01-01 12:01:00'),
    ]);

    $collection = new MessageCollection($messages);
    $textMessages = $collection->filterByType('text');

    expect($textMessages->count())->toBe(1)
        ->and($textMessages->first()->type)->toBe('text');
});

test('message collection can filter by user', function (): void {
    $messages = collect([
        new MessageResult(1, 10, 5, 'Message 1', 'text', null, null, '2024-01-01 12:00:00'),
        new MessageResult(2, 10, 6, 'Message 2', 'text', null, null, '2024-01-01 12:01:00'),
        new MessageResult(3, 10, 5, 'Message 3', 'text', null, null, '2024-01-01 12:02:00'),
    ]);

    $collection = new MessageCollection($messages);
    $user5Messages = $collection->filterByUser(5);

    expect($user5Messages->count())->toBe(2);
});
