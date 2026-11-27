<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kegiatan that committee members add to an event's calendar themselves (for example a
     * site survey or a rehearsal). Meetings and task deadlines are never copied here: the
     * calendar reads them live from their own tables.
     *
     * Dates and times are wall-clock values in the organization's timezone, like meetings.
     * ends_on equals starts_on for a one-day kegiatan; times are only used on one-day ones.
     */
    public function up(): void
    {
        Schema::create('calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('location', 200)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id', 'event_id']);
            $table->index(['event_id', 'starts_on']);

            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_entries');
    }
};
