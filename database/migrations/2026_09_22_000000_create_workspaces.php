<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });
        Schema::create('workspace_members', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();
            $table->primary(['workspace_id', 'user_id']);
        });
        Schema::create('workspace_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
        });
        // Nullable only for records created by previous versions. New writes require a workspace.
        Schema::table('assignees', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->unique(['workspace_id', 'user_id']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->restrictOnDelete();
            $table->index(['workspace_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'created_at', 'id']);
            $table->dropConstrainedForeignId('workspace_id');
        });
        Schema::table('assignees', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('workspace_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_workspace_id');
        });
        Schema::dropIfExists('workspace_invites');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
