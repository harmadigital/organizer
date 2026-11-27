<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The member directory moved into the users table (decision K-15, option B).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Refuses to run while the table still holds people, so no one is lost silently.
     */
    public function up(): void
    {
        $remainingMembers = DB::table('members')->count();

        if ($remainingMembers > 0) {
            throw new RuntimeException("The members table still holds {$remainingMembers} rows. Move them into users before dropping it.");
        }

        Schema::drop('members');
    }

    /**
     * Reverse the migrations. Restores the empty table as it was created in Batch 1.1.
     */
    public function down(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('full_name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'email']);
            $table->unique(['organization_id', 'phone']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'full_name']);
        });
    }
};
