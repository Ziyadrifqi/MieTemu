<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu sesi antrean per cabang per tanggal. Kolom last_ticket_number
        // adalah penghitung nomor yang dikunci saat tiket dibuat.
        Schema::create('queue_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->string('status', 20)->default('open'); // open, closed
            $table->unsignedInteger('last_ticket_number')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'session_date']);
        });

        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name', 100)->nullable();
            $table->string('source', 20)->default('virtual'); // virtual, walk_in
            $table->unsignedInteger('ticket_number');
            $table->unsignedTinyInteger('party_size');
            $table->string('status', 20)->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('seated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Jaring pengaman di level database: nomor tidak mungkin dobel dalam satu sesi.
            $table->unique(['queue_session_id', 'ticket_number']);
            $table->index(['queue_session_id', 'status', 'ticket_number']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('queue_ticket_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_ticket_status_histories');
        Schema::dropIfExists('queue_tickets');
        Schema::dropIfExists('queue_sessions');
    }
};
