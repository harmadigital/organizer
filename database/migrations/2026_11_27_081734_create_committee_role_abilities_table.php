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
        Schema::create('committee_role_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_role_id')->constrained()->cascadeOnDelete();
            $table->string('ability', 60);
            $table->timestamps();

            $table->unique(['committee_role_id', 'ability']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_role_abilities');
    }
};
