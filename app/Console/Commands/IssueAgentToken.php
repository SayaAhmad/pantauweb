<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IssueAgentToken extends Command
{
    protected $signature = 'servers:agent-token {server : ID atau nama server}';

    protected $description = 'Terbitkan token agent untuk sebuah server (token hanya ditampilkan sekali)';

    public function handle(): int
    {
        $identifier = (string) $this->argument('server');

        $server = Server::query()
            ->where('id', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if ($server === null) {
            $this->error("Server \"{$identifier}\" tidak ditemukan.");

            return self::FAILURE;
        }

        $token = Str::random(48);

        $server->forceFill(['agent_token_hash' => Hash::make($token)])->save();

        $this->newLine();
        $this->info("Token agent untuk {$server->name}:");
        $this->line($token);
        $this->newLine();
        $this->comment('Simpan token ini sekarang. Token tidak dapat ditampilkan lagi.');
        $this->comment('Kirim ke: POST /api/agent/metrics dengan header X-Agent-Token.');

        return self::SUCCESS;
    }
}
