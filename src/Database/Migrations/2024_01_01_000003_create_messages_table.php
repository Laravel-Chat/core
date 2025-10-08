<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('chat.tables.messages', 'messages');
        $conversationsTable = config('chat.tables.conversations', 'conversations');
        $userTable = (new (config('chat.user_model'))())->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($conversationsTable, $userTable): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained($conversationsTable)->onDelete('cascade');
            $table->foreignId('user_id')->constrained($userTable)->onDelete('cascade');
            $table->text('content');
            $table->string('type')->default('text');
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        $tableName = config('chat.tables.messages', 'messages');
        Schema::dropIfExists($tableName);
    }
};
