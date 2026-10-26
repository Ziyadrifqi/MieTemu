<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->string('name', 50);
            $t->unsignedTinyInteger('capacity');
            $t->string('status', 20)->default('available');
            $t->timestamps();

            $t->unique(['branch_id', 'name']);
            $t->index(['branch_id', 'status']);
        });

        Schema::create('dining_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('dining_table_id')->constrained('dining_tables')->cascadeOnDelete();
            $t->foreignId('queue_ticket_id')->nullable()->unique()->constrained('queue_tickets')->nullOnDelete();
            $t->unsignedTinyInteger('party_size');
            $t->timestamp('seated_at');
            $t->timestamp('ended_at')->nullable();
            $t->foreignId('seated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['dining_table_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_sessions');
        Schema::dropIfExists('dining_tables');
    }
};
