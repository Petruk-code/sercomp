<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:backup-database
        {--connection= : Connection name to back up (defaults to the app default connection)}
        {--dir= : Directory where backups are stored}
        {--keep=14 : Number of backups to retain, oldest pruned first}';

    /**
     * @var string
     */
    protected $description = 'Create a database backup and prune old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = $this->option('connection') ?: (string) config('database.default');
        $directory = $this->option('dir') ?: (string) storage_path('app/backups');

        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            $this->components->error("Unknown database connection [{$connection}].");

            return self::FAILURE;
        }

        $driver = $config['driver'] ?? null;
        $timestamp = now()->format('Y-m-d_H-i-s');

        $file = match ($driver) {
            'sqlite' => $this->backupSqlite($config, $directory, $timestamp),
            'mysql' => $this->backupMySql($config, $directory, $timestamp),
            'pgsql' => $this->backupPgSql($config, $directory, $timestamp),
            default => null,
        };

        if ($file === null) {
            $this->components->error("Backup is not supported for the [{$driver}] driver.");

            return self::FAILURE;
        }

        $this->pruneBackups($directory, (int) $this->option('keep'));

        $this->components->info("Database backed up to {$file}.");

        return self::SUCCESS;
    }

    /**
     * Copy a file-backed SQLite database (safe even with a WAL journal).
     *
     * @param  array<string, mixed>  $config
     */
    protected function backupSqlite(array $config, string $directory, string $timestamp): string
    {
        $database = $config['database'] ?? null;

        if (! is_string($database) || $database === ':memory:') {
            throw new \RuntimeException('The SQLite connection must use a file-backed database to be backed up.');
        }

        File::ensureDirectoryExists($directory);

        $source = str_starts_with($database, '/') ? $database : base_path($database);
        $destination = $directory.'/db-'.$timestamp.'.sqlite';

        File::copy($source, $destination);

        return $destination;
    }

    /**
     * Dump a MySQL database via the mysqldump binary.
     *
     * @param  array<string, mixed>  $config
     */
    protected function backupMySql(array $config, string $directory, string $timestamp): string
    {
        File::ensureDirectoryExists($directory);

        $destination = $directory.'/db-'.$timestamp.'.sql';

        $arguments = [
            $config['host'] ?? '127.0.0.1',
            (string) ($config['port'] ?? 3306),
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            (string) ($config['database'] ?? ''),
        ];

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines %s > %s',
            escapeshellarg($arguments[0]),
            escapeshellarg($arguments[1]),
            escapeshellarg($arguments[2]),
            escapeshellarg($arguments[3]),
            escapeshellarg($arguments[4]),
            escapeshellarg($destination),
        );

        $result = Process::run($command);

        if ($result->failed()) {
            throw new \RuntimeException('mysqldump failed: '.$result->errorOutput());
        }

        return $destination;
    }

    /**
     * Dump a PostgreSQL database via the pg_dump binary.
     *
     * @param  array<string, mixed>  $config
     */
    protected function backupPgSql(array $config, string $directory, string $timestamp): string
    {
        File::ensureDirectoryExists($directory);

        $destination = $directory.'/db-'.$timestamp.'.sql';

        $arguments = [
            (string) ($config['host'] ?? '127.0.0.1'),
            (string) ($config['port'] ?? 5432),
            (string) ($config['username'] ?? ''),
            (string) ($config['database'] ?? ''),
        ];

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s %s > %s',
            escapeshellarg((string) ($config['password'] ?? '')),
            escapeshellarg($arguments[0]),
            escapeshellarg($arguments[1]),
            escapeshellarg($arguments[2]),
            escapeshellarg($arguments[3]),
            escapeshellarg($destination),
        );

        $result = Process::run($command);

        if ($result->failed()) {
            throw new \RuntimeException('pg_dump failed: '.$result->errorOutput());
        }

        return $destination;
    }

    /**
     * Remove the oldest backup files beyond the retention limit.
     */
    protected function pruneBackups(string $directory, int $keep): void
    {
        if ($keep < 1) {
            return;
        }

        $files = collect(File::glob($directory.'/db-*'))
            ->sortByDesc(fn (string $path): int => File::lastModified($path))
            ->values();

        $files->slice($keep)->each(fn (string $path): bool => File::delete($path));
    }
}
