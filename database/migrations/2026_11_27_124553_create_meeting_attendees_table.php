<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who attended, as written down in the minutes.
     */
    public function up(): void
    {
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('event_membership_id');
            $table->timestamps();

            $table->unique(['meeting_id', 'event_membership_id']);
            $table->foreign(['meeting_id', 'event_id'])->references(['id', 'event_id'])->on('meetings')->cascadeOnDelete();
            $table->foreign(['event_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};
