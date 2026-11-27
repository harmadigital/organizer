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
        Schema::create('event_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('event_membership_id');
            $table->foreignId('committee_role_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->text('job_description')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('ended_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            // Set while the assignment is active, null once it ends: one active assignment
            // per person, role and division, and one active primary holder of a singleton role.
            $table->string('active_key', 100)->nullable()->unique();
            $table->string('primary_key', 100)->nullable()->unique();
            $table->timestamps();

            $table->index(['event_id', 'committee_role_id']);
            $table->index(['event_membership_id', 'ends_at']);
            $table->index(['division_id', 'ends_at']);

            // The membership and the division must belong to the same event as the assignment.
            $table->foreign(['event_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->cascadeOnDelete();
            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_role_assignments');
    }
};
