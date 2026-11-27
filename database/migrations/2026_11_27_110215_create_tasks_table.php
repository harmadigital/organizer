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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('work_program_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_membership_id')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('todo');
            $table->unsignedTinyInteger('weight')->default(1);
            $table->boolean('requires_review')->default(false);
            $table->boolean('is_milestone')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id', 'event_id']);
            $table->index(['event_id', 'status', 'due_on']);
            $table->index(['division_id', 'status']);
            $table->index(['owner_membership_id', 'status']);
            $table->index(['parent_id']);

            // Division, program, parent task and owner must all belong to the same event.
            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
            $table->foreign(['work_program_id', 'event_id'])->references(['id', 'event_id'])->on('work_programs')->restrictOnDelete();
            $table->foreign(['parent_id', 'event_id'])->references(['id', 'event_id'])->on('tasks')->restrictOnDelete();
            $table->foreign(['owner_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
