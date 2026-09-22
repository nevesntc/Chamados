<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high']);
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed']);
            $table->foreignId('assignee_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['assignee_id', 'status']);
            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('assignees');
    }
};
