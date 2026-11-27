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
        Schema::create('work_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('division_id');
            $table->string('title', 200);
            $table->text('objective')->nullable();
            $table->text('output')->nullable();
            $table->text('indicator')->nullable();
            $table->unsignedBigInteger('pic_membership_id')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id', 'event_id']);
            $table->index(['event_id', 'division_id', 'status']);

            // The division and the person in charge must belong to the same event.
            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
            $table->foreign(['pic_membership_id', 'event_id'])->references(['id', 'event_id'])->on('event_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_programs');
    }
};
