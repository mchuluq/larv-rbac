<?php namespace Mchuluq\Larv\Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mchuluq\Larv\Rbac\Models\RememberToken;

class SyncSessionsAndTokens extends Command{

    protected $signature = 'sessions:sync {--cleanup : Remove orphaned records}';

    protected $description = 'Sync sessions and remember tokens';

    public function handle(){
        if (config('session.driver') !== 'database') {
            $this->error('Session driver must be database');
            return 1;
        }

        $sessionTable = config('session.table', 'sessions');
        $cleanup = $this->option('cleanup');

        // Find sessions without valid remember_token
        $orphanedSessions = DB::table($sessionTable)->whereNotNull('remember_token')->whereNotExists(function ($query) use ($sessionTable) {
            $query->select(DB::raw(1))->from('remember_tokens')->whereColumn('remember_tokens.id', '=', "{$sessionTable}.remember_token");
        })->count();

        $this->info("Found {$orphanedSessions} sessions with invalid remember_token");

        // Find tokens with active sessions
        $tokensWithSession = RememberToken::whereExists(function ($query) use ($sessionTable) {
            $query->select(DB::raw(1))->from($sessionTable)->whereColumn('remember_tokens.id', '=', "{$sessionTable}.remember_token");
        })->count();

        // Find tokens without sessions
        $tokensWithoutSession = RememberToken::whereNotExists(function ($query) use ($sessionTable) {
            $query->select(DB::raw(1))->from($sessionTable)->whereColumn('remember_tokens.id', '=', "{$sessionTable}.remember_token");
        })->count();

        $this->table(
            ['Type', 'Count'],
            [
                ['Orphaned sessions (invalid token_id)', $orphanedSessions],
                ['Tokens with active session', $tokensWithSession],
                ['Tokens without session (idle)', $tokensWithoutSession],
            ]
        );

        if ($cleanup) {
            // Cleanup orphaned sessions
            $deleted = DB::table($sessionTable)->whereNotNull('remember_token')->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('remember_tokens')->whereColumn('remember_tokens.id', '=', 'sessions.remember_token');
            })->update(['remember_token' => null]);
            $this->info("Cleaned {$deleted} orphaned sessions");
        }
        return 0;
    }
}