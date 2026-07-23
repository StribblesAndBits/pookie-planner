<?php

namespace App\Console\Commands;

use App\Models\JulesDay;
use Illuminate\Console\Command;

class DeleteJulesDaysCommand extends Command
{
    protected $signature = 'app:delete-jules-days';

    protected $description = 'Delete all Jules Days from the database';

    public function handle(): int
    {
        $count = JulesDay::count();

        if ($count === 0) {
            $this->info('No Jules Days to delete.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} Jules Day(s) in the database.");

        if (!$this->confirm('Are you sure you want to delete all Jules Days?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        JulesDay::query()->delete();

        $this->info("Successfully deleted {$count} Jules Day(s).");

        return self::SUCCESS;
    }
}
