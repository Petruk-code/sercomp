<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = storage_path('framework/testing/backups');
    File::deleteDirectory($this->dir);
    File::ensureDirectoryExists($this->dir);

    $this->dbFile = $this->dir.'/source.sqlite';
    File::put($this->dbFile, '');

    config(['database.connections.backup_test' => [
        'driver' => 'sqlite',
        'database' => $this->dbFile,
    ]]);

    $this->artisan('migrate', ['--database' => 'backup_test', '--force' => true]);

    DB::connection('backup_test')->table('users')->insert([
        'name' => 'Backup Tester',
        'email' => 'backup@sercomp.test',
        'password' => 'secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

afterEach(function () {
    DB::purge('backup_test');
    File::deleteDirectory($this->dir);
});

test('the backup command copies a file-backed sqlite database', function () {
    $this->artisan('app:backup-database', [
        '--connection' => 'backup_test',
        '--dir' => $this->dir,
    ])->assertSuccessful();

    $backup = File::glob($this->dir.'/db-*.sqlite');

    expect($backup)->toHaveCount(1);

    $handle = new PDO('sqlite:'.$backup[0]);
    $count = $handle->query('select count(*) from users')->fetchColumn();

    expect((int) $count)->toBe(1);
});

test('the backup command prunes old backups beyond the retention limit', function () {
    File::put($this->dir.'/db-oldest.sqlite', 'x');
    File::put($this->dir.'/db-old.sqlite', 'x');

    $this->artisan('app:backup-database', [
        '--connection' => 'backup_test',
        '--dir' => $this->dir,
        '--keep' => 1,
    ])->assertSuccessful();

    $remaining = File::glob($this->dir.'/db-*.sqlite');

    expect($remaining)->toHaveCount(1);
});

test('the backup command fails for unsupported drivers', function () {
    config(['database.connections.backup_test' => ['driver' => 'sqlsrv']]);

    $this->artisan('app:backup-database', [
        '--connection' => 'backup_test',
        '--dir' => $this->dir,
    ])->assertFailed();
});
