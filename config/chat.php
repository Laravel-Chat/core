<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the User model that will be used for conversations.
    |
    */

    'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | If you need to extend the package models, you can specify your custom
    | models here. They must extend the package's base models.
    |
    */

    'models' => [
        'conversation' => Akira\LaravelChat\Models\Conversation::class,
        'message' => Akira\LaravelChat\Models\Message::class,
        'conversation_participant' => Akira\LaravelChat\Models\ConversationParticipant::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package.
    |
    */

    'tables' => [
        'conversations' => 'conversations',
        'messages' => 'messages',
        'conversation_participants' => 'conversation_participants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configure real-time broadcasting settings.
    |
    */

    'broadcasting' => [
        'enabled' => env('CHAT_BROADCASTING_ENABLED', true),
        'channel_prefix' => env('CHAT_CHANNEL_PREFIX', 'chat'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for conversations and messages.
    |
    */

    'pagination' => [
        'conversations_per_page' => 20,
        'messages_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Types
    |--------------------------------------------------------------------------
    |
    | Define allowed message types. You can add custom types here.
    |
    */

    'message_types' => [
        'text',
        'image',
        'file',
        'system',
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation Types
    |--------------------------------------------------------------------------
    |
    | Define allowed conversation types.
    |
    */

    'conversation_types' => [
        'direct',
        'group',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Configure custom policies for message sending and other features.
    | Set to null to use the default policy (allows all messages).
    |
    */

    'policies' => [
        // Message policy - controls who can send messages to whom
        // Options:
        // - null (default): Akira\LaravelChat\Policies\DefaultMessagePolicy - allows all
        // - Akira\LaravelChat\Policies\FollowerMessagePolicy - only between followers
        // - Your custom policy implementing MessagePolicyContract
        'message_policy' => env('CHAT_MESSAGE_POLICY', null),
    ],

];
