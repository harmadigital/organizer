<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Users become the single directory of people. A person may be recorded without
 * login access, so username, email and password become optional.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->foreignId('organization_unit_id')->nullable()->after('phone')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active')->index()->after('organization_unit_id');
            $table->text('notes')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Refuses while some people have no username, email or password, because those
     * columns cannot become required again without inventing values for them.
     */
    public function down(): void
    {
        $peopleWithoutLogin = DB::table('users')
            ->where(fn ($query) => $query->whereNull('username')->orWhereNull('email')->orWhereNull('password'))
            ->count();

        if ($peopleWithoutLogin > 0) {
            throw new RuntimeException("{$peopleWithoutLogin} users have no username, email or password. Give them one or remove them before rolling back.");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_unit_id']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropColumn(['phone', 'organization_unit_id', 'status', 'notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
