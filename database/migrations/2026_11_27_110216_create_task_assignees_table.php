<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('event_membership_id');
            $table->timestamps();

            $table->unique(['task_id', 'event_membership_id']);
            $table->index('event_membership_id');

            $table->foreign(['task_id', 'event_id'])->references(['id', 'event_id'])->on('tasks')->cascadeOnDelete();
            $table->foreign(['event_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
