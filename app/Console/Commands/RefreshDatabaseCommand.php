<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild every table and reseed (deletes all data; local and testing only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->components->error('db:refresh deletes all data and only runs in the local or testing environment.');

            return self::FAILURE;
        }

        if ($this->call('migrate:refresh') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->call('db:seed');
        $this->info('All database and seeder has been execute');

        return self::SUCCESS;
    }
}
