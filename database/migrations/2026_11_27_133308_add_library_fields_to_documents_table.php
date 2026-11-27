<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The event's shared media and document library: a document is either a stored file or
     * a link, optionally tagged with the division it belongs to.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('event_id');
            $table->string('kind', 10)->default('file')->after('title');
            $table->string('url', 2000)->nullable()->after('kind');

            $table->foreign(['division_id', 'event_id'])->references(['id', 'event_id'])->on('divisions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['division_id', 'event_id']);
            $table->dropColumn(['division_id', 'kind', 'url']);
        });
    }
};
