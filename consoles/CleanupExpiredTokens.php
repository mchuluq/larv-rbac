<?php namespace Mchuluq\Larv\Rbac\Commands;

use Illuminate\Console\Command;
use Mchuluq\Larv\Rbac\Models\RememberToken;
use Carbon\Carbon;

class CleanupExpiredTokens extends Command{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup 
                            {--days=30 : Delete tokens older than specified days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired remember tokens';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(){
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Cleaning up remember tokens...");
        $this->line("");

        // Get expired tokens
        $expiredQuery = RememberToken::where('expires_at', '<', Carbon::now());
        $expiredCount = $expiredQuery->count();

        // Get old unused tokens
        $oldUnusedQuery = RememberToken::where('last_used_at', '<', Carbon::now()->subDays($days))
            ->orWhereNull('last_used_at');
        $oldUnusedCount = $oldUnusedQuery->count();

        // Show statistics
        $this->table(
            ['Type', 'Count'],
            [
                ['Expired tokens', $expiredCount],
                ["Old unused tokens (>{$days} days)", $oldUnusedCount],
            ]
        );

        if ($dryRun) {
            $this->warn("DRY RUN - No tokens were deleted");
            return 0;
        }

        // Confirm deletion
        if (!$this->confirm('Do you want to delete these tokens?', true)) {
            $this->info('Operation cancelled');
            return 0;
        }

        // Delete expired tokens
        $deletedExpired = $expiredQuery->delete();
        $this->info("✓ Deleted {$deletedExpired} expired tokens");

        // Delete old unused tokens
        $deletedOld = RememberToken::where('last_used_at', '<', Carbon::now()->subDays($days))->orWhereNull('last_used_at')->delete();
        $this->info("✓ Deleted {$deletedOld} old unused tokens");

        $this->line("");
        $this->info("Total deleted: " . ($deletedExpired + $deletedOld) . " tokens");

        return 0;
    }
}