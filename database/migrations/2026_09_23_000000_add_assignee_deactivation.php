<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quem sai da equipe deixa de receber chamados, mas segue nomeado nos que já atendeu.
        Schema::table('assignees', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable();
            $table->index(['workspace_id', 'deactivated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('assignees', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'deactivated_at']);
            $table->dropColumn('deactivated_at');
        });
    }
};
