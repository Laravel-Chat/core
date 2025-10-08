<?php

declare(strict_types=1);

use Akira\LaravelChat\Config\ChatConfig;

test('chat config can be instantiated', function (): void {
    $config = ChatConfig::getInstance();

    expect($config)->toBeInstanceOf(ChatConfig::class);
});

test('chat config returns correct user model', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->getUserModel())->toBeString();
});

test('chat config returns correct table names', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->getConversationsTable())->toBe('conversations')
        ->and($config->getMessagesTable())->toBe('messages')
        ->and($config->getConversationParticipantsTable())->toBe('conversation_participants');
});

test('chat config validates message types', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->isValidMessageType('text'))->toBeTrue()
        ->and($config->isValidMessageType('image'))->toBeTrue()
        ->and($config->isValidMessageType('invalid'))->toBeFalse();
});

test('chat config validates conversation types', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->isValidConversationType('direct'))->toBeTrue()
        ->and($config->isValidConversationType('group'))->toBeTrue()
        ->and($config->isValidConversationType('invalid'))->toBeFalse();
});

test('chat config returns message policy', function (): void {
    $config = ChatConfig::getInstance();

    // By default, policy should be null
    expect($config->getMessagePolicy())->toBeNull();
});

test('chat config broadcasting settings', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->isBroadcastingEnabled())->toBeBool()
        ->and($config->getBroadcastingChannelPrefix())->toBeString();
});

test('chat config pagination settings', function (): void {
    $config = ChatConfig::getInstance();

    expect($config->getConversationsPerPage())->toBeInt()
        ->and($config->getMessagesPerPage())->toBeInt()
        ->and($config->getConversationsPerPage())->toBeGreaterThan(0)
        ->and($config->getMessagesPerPage())->toBeGreaterThan(0);
});
