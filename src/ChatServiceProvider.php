<?php

declare(strict_types=1);

namespace Akira\LaravelChat;

use Akira\LaravelChat\Config\ChatConfig;
use Akira\LaravelChat\Support\ConversationManager;
use Akira\LaravelChat\Support\MessageManager;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/chat.php',
            'chat'
        );

        // Register ChatConfig as singleton
        $this->app->singleton(ChatConfig::class, fn (): \Akira\LaravelChat\Config\ChatConfig => ChatConfig::getInstance());

        // Register Managers for Facades
        $this->app->singleton('chat.conversation', ConversationManager::class);
        $this->app->singleton('chat.message', MessageManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/chat.php' => config_path('chat.php'),
            ], 'laravel-chat-config');

            $this->publishes([
                __DIR__.'/Database/Migrations/' => database_path('migrations'),
            ], 'laravel-chat-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
