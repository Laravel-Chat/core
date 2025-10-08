<?php

declare(strict_types=1);

use Akira\LaravelChat\Policies\DefaultMessagePolicy;
use Akira\LaravelChat\Tests\User;

test('default message policy allows all messages', function (): void {
    $policy = new DefaultMessagePolicy();

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

    expect($policy->canSendMessage($user1, $user2))->toBeTrue()
        ->and($policy->canReceiveMessage($user2, $user1))->toBeTrue();
});

test('user can check if can receive messages from another user', function (): void {
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

    // With default policy, should return true
    expect($user1->canReceiveMessagesFrom($user2))->toBeTrue()
        ->and($user2->canReceiveMessagesFrom($user1))->toBeTrue();
});

test('user can check if can send messages to another user', function (): void {
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

    // With default policy, should return true
    expect($user1->canSendMessagesTo($user2))->toBeTrue()
        ->and($user2->canSendMessagesTo($user1))->toBeTrue();
});
