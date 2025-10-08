<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('chat.tables.conversations', 'conversations');
        $userTable = (new (config('chat.user_model'))())->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userTable): void {
            $table->id();
            $table->string('title')->nullable();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->foreignId('created_by')->constrained($userTable)->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        $tableName = config('chat.tables.conversations', 'conversations');
        Schema::dropIfExists($tableName);
    }
};
