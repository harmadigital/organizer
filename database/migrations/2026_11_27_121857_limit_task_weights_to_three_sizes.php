<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Task size is now light (1), medium (2) or heavy (3); larger weights from the old
     * 1-10 scale become heavy.
     */
    public function up(): void
    {
        DB::table('tasks')->where('weight', '>', 3)->update(['weight' => 3]);
    }

    /**
     * The original weights are not kept, so there is nothing to restore.
     */
    public function down(): void
    {
        //
    }
};
