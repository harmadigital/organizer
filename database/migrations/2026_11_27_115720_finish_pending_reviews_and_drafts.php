<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tasks no longer wait for review and are no longer drafts: a task submitted for review
     * is finished, a draft is open. Their status history is left as written.
     */
    public function up(): void
    {
        DB::table('tasks')->where('status', 'pending_review')->update(['status' => 'completed', 'completed_at' => DB::raw('coalesce(completed_at, updated_at)')]);
        DB::table('tasks')->where('status', 'draft')->update(['status' => 'todo']);
    }

    /**
     * Nothing to restore: which tasks were waiting for review is not needed anymore.
     */
    public function down(): void
    {
        //
    }
};
