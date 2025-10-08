<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('chat.tables.conversation_participants', 'conversation_participants');
        $conversationsTable = config('chat.tables.conversations', 'conversations');
        $userTable = (new (config('chat.user_model'))())->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($conversationsTable, $userTable): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained($conversationsTable)->onDelete('cascade');
            $table->foreignId('user_id')->constrained($userTable)->onDelete('cascade');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        $tableName = config('chat.tables.conversation_participants', 'conversation_participants');
        Schema::dropIfExists($tableName);
    }
};
