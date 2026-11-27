<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A committee meeting. Every committee member sees it; there are no invitations.
     * The minutes are stored on the meeting itself, every saved text also in
     * meeting_minutes_versions.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->string('title', 200);
            $table->date('meets_on');
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('location', 200)->nullable();
            $table->text('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->unsignedInteger('minutes_version')->default(0);
            $table->timestamp('minutes_updated_at')->nullable();
            $table->foreignId('minutes_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id', 'event_id']);
            $table->index(['event_id', 'meets_on']);

            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
