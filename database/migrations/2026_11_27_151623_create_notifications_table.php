<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * In-app notices (the lonceng), in Laravel's standard notifications table.
     *
     * Two extra columns: event_id, so notices of an event the person left can be hidden, and
     * subject ("task:12", "meeting:7"), so opening that page clears its notices. The id is
     * derived from the fact and the person, so the same notice is never written twice.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('subject', 60)->nullable();
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_latest_index');
            $table->index('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
