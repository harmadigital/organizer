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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 200);
            $table->string('theme', 255)->nullable();
            $table->string('subtheme', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('background')->nullable();
            $table->unsignedInteger('target_participants')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('committee_starts_on')->nullable();
            $table->date('committee_ends_on')->nullable();
            $table->string('location_text', 255)->nullable();
            $table->unsignedBigInteger('budget_target_amount')->nullable();
            $table->char('currency_code', 3)->default('IDR');
            $table->string('status', 20)->default('draft');
            $table->string('phase', 30)->default('initiation');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
