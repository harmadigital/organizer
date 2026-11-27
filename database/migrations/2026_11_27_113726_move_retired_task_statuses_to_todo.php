<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "In progress" and "blocked" are no longer statuses: open tasks in them become "todo".
     * Their status history is left as written.
     */
    public function up(): void
    {
        DB::table('tasks')->whereIn('status', ['in_progress', 'blocked'])->update(['status' => 'todo']);
    }

    /**
     * Nothing to restore: which open tasks were in progress is not recoverable, nor needed.
     */
    public function down(): void
    {
        //
    }
};
