<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A work program without a division belongs to the core team (Ketua, Sekretaris, Bendahara).
     */
    public function up(): void
    {
        Schema::table('work_programs', function (Blueprint $table) {
            $table->dropForeign(['division_id', 'event_id']);
        });

        Schema::table('work_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->change();
            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }

    /**
     * Core team programs must be moved to a division or deleted before this can run.
     */
    public function down(): void
    {
        Schema::table('work_programs', function (Blueprint $table) {
            $table->dropForeign(['division_id', 'event_id']);
        });

        Schema::table('work_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable(false)->change();
            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }
};
