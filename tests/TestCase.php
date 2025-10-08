<?php

declare(strict_types=1);

namespace Akira\LaravelChat\Tests;

use Akira\LaravelChat\ChatServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Akira\\LaravelChat\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            ChatServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        config()->set('chat.user_model', User::class);
        config()->set('chat.tables.conversations', 'conversations');
        config()->set('chat.tables.conversation_participants', 'conversation_participants');
        config()->set('chat.tables.messages', 'messages');
        config()->set('chat.models.conversation', \Akira\LaravelChat\Models\Conversation::class);
        config()->set('chat.models.message', \Akira\LaravelChat\Models\Message::class);
        config()->set('chat.models.conversation_participant', \Akira\LaravelChat\Models\ConversationParticipant::class);
        config()->set('chat.broadcasting.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../src/Database/Migrations');

        // Create users table for testing
        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('migrate:reset');
        });
    }
}
