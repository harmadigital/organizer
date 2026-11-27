<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow-ups agreed in a meeting. Each becomes exactly one task.
     */
    public function up(): void
    {
        Schema::create('meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('meeting_id');
            $table->string('title', 200);
            $table->unsignedBigInteger('owner_membership_id')->nullable();
            $table->date('due_on')->nullable();
            $table->unsignedBigInteger('task_id')->nullable()->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['meeting_id', 'sort_order']);
            $table->foreign(['meeting_id', 'event_id'])->references(['id', 'event_id'])->on('meetings')->cascadeOnDelete();
            $table->foreign(['owner_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
            $table->foreign(['task_id', 'event_id'])->references(['id', 'event_id'])->on('tasks')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_action_items');
    }
};
