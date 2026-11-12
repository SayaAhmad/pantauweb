<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class GitSync extends Command
{
    protected $signature = 'pantau:git-sync
                            {--message= : Pesan commit kustom}
                            {--no-push : Commit saja, tanpa push ke remote}
                            {--dry-run : Tampilkan perubahan tanpa commit}';

    protected $description = 'Commit dan push otomatis perubahan aplikasi ke git';

    public function handle(): int
    {
        $root = $this->git(['rev-parse', '--show-toplevel']);

        if ($root === null) {
            $this->error('Direktori ini bukan repositori git.');

            return self::FAILURE;
        }

        $root = trim($root);

        if ($this->git(['rev-parse', '-q', '--verify', 'MERGE_HEAD'], $root) !== null
            || $this->git(['rev-parse', '-q', '--verify', 'REBASE_HEAD'], $root) !== null) {
            $this->error('Merge atau rebase sedang berlangsung. Selesaikan dulu sebelum sinkronisasi.');

            return self::FAILURE;
        }

        $status = trim($this->git(['status', '--porcelain'], $root) ?? '');

        if ($status === '') {
            $this->info('Tidak ada perubahan untuk di-commit.');

            return $this->option('no-push') ? self::SUCCESS : $this->push($root);
        }

        if ($this->option('dry-run')) {
            $this->line($status);

            return self::SUCCESS;
        }

        $this->git(['add', '-A'], $root);

        $message = $this->option('message')
            ?: 'chore(sync): perbarui aplikasi otomatis '.now()->format('Y-m-d H:i');

        if ($this->git(['commit', '-m', $message], $root) === null) {
            $this->error('Commit gagal.');

            return self::FAILURE;
        }

        $this->info("Commit dibuat: {$message}");

        return $this->option('no-push') ? self::SUCCESS : $this->push($root);
    }

    private function push(string $root): int
    {
        $branch = trim($this->git(['rev-parse', '--abbrev-ref', 'HEAD'], $root) ?? '');

        if ($branch === '' || $branch === 'HEAD') {
            $this->error('Tidak berada di branch yang bisa di-push.');

            return self::FAILURE;
        }

        if ($this->git(['remote', 'get-url', 'origin'], $root) === null) {
            $this->error('Remote origin belum dikonfigurasi.');

            return self::FAILURE;
        }

        $result = Process::path($root)
            ->env(['GIT_TERMINAL_PROMPT' => '0'])
            ->timeout(120)
            ->run(['git', 'push', '-u', 'origin', 'HEAD']);

        if ($result->failed()) {
            $this->error('Push gagal: '.trim($result->errorOutput() ?: $result->output()));

            return self::FAILURE;
        }

        $this->info("Push ke origin/{$branch} selesai.");

        return self::SUCCESS;
    }

    private function git(array $args, ?string $path = null): ?string
    {
        $result = Process::path($path ?? base_path())
            ->env(['GIT_TERMINAL_PROMPT' => '0'])
            ->timeout(120)
            ->run(['git', ...$args]);

        return $result->successful() ? $result->output() : null;
    }
}
