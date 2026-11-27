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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('author_membership_id');
            $table->date('activity_on');
            $table->text('activity');
            $table->text('result')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'activity_on']);

            $table->foreign(['task_id', 'event_id'])->references(['id', 'event_id'])->on('tasks')->cascadeOnDelete();
            $table->foreign(['author_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
