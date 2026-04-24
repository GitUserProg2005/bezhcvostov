<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DatabaseClusterTestCommand extends Command
{
    protected $signature = 'db:cluster-test
                            {--expect-primary-down : После «docker stop postgres-primary»: проверить чтение через Pgpool и ожидаемый отказ записи}
                            {--skip-replica : Не проверять появление строки на реплике (только запись через Pgpool)}';

    protected $description = 'Проверка Pgpool + primary/replica: репликация и (опционально) поведение при остановке primary';

    private const TABLE = 'pg_cluster_probe';

    public function handle(): int
    {
        if ($this->option('expect-primary-down')) {
            return $this->runFailoverScenario();
        }

        return $this->runReplicationScenario();
    }

    private function runReplicationScenario(): int
    {
        $this->info('1) Подключение через default (Pgpool / '.config('database.connections.pgsql.host').':'.config('database.connections.pgsql.port').')…');

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (Throwable $e) {
            $this->error('Не удалось подключиться: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('   OK');

        DB::statement('CREATE TABLE IF NOT EXISTS '.self::TABLE.' (
            id BIGSERIAL PRIMARY KEY,
            token TEXT NOT NULL UNIQUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        $token = Str::uuid()->toString();
        $now = now()->toDateTimeString();

        $this->info('2) INSERT через Pgpool (запись на primary)…');
        DB::table(self::TABLE)->insert([
            'token' => $token,
            'created_at' => $now,
        ]);
        $this->line('   token = '.$token);

        if ($this->option('skip-replica')) {
            $this->warn('Пропуск проверки реплики (--skip-replica).');

            return self::SUCCESS;
        }

        $this->info('3) Ожидание строки на реплике (прямое подключение '.config('database.connections.pgsql_replica.host').':'.config('database.connections.pgsql_replica.port').')…');

        try {
            DB::connection('pgsql_replica')->getPdo();
        } catch (Throwable $e) {
            $this->error('Реплика недоступна: '.$e->getMessage());
            $this->comment('Убедитесь: postgres-replica запущен, порт 5433 проброшен, DB_REPLICA_HOST/DB_REPLICA_PORT при необходимости.');

            return self::FAILURE;
        }

        $deadline = microtime(true) + 15.0;
        $ok = false;
        while (microtime(true) < $deadline) {
            $exists = DB::connection('pgsql_replica')
                ->table(self::TABLE)
                ->where('token', $token)
                ->exists();
            if ($exists) {
                $ok = true;
                break;
            }
            usleep(150_000);
        }

        if (! $ok) {
            $this->error('Строка за 15 с на реплике не появилась — проверьте streaming replication и пароли.');

            return self::FAILURE;
        }

        $this->line('   OK — репликация работает.');

        $this->newLine();
        $this->comment('Ручной failover: docker stop postgres-primary');
        $this->comment('Затем: php artisan db:cluster-test --expect-primary-down');

        return self::SUCCESS;
    }

    private function runFailoverScenario(): int
    {
        $this->warn('Ожидается, что контейнер postgres-primary остановлен (docker stop postgres-primary).');
        $this->newLine();

        $this->info('1) SELECT через Pgpool…');
        $pgpoolOk = false;

        try {
            DB::select('select 1');
            $count = DB::table(self::TABLE)->count();
            $this->line('   OK через Pgpool, строк в '.self::TABLE.': '.$count);
            $pgpoolOk = true;
        } catch (Throwable $e) {
            $this->line('   Pgpool: '.$e->getMessage());
            $this->comment('   Без Patroni/repmgr Pgpool не «поднимает» standby в primary — соединение часто падает.');
        }

        if (! $pgpoolOk) {
            $this->newLine();
            $this->info('2) Прямое чтение с реплики (обход Pgpool, порт '.config('database.connections.pgsql_replica.port').')…');

            try {
                DB::connection('pgsql_replica')->getPdo();
                $count = DB::connection('pgsql_replica')->table(self::TABLE)->count();
                $this->line('   OK — standby принимает SELECT, строк: '.$count);
                $this->comment('   Это и есть «данные живы на реплике»; приложение через Pgpool без HA может не пережить остановку primary.');
            } catch (Throwable $e) {
                $this->error('   Реплика недоступна: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $this->newLine();

        if (! $pgpoolOk) {
            $this->info('3) INSERT через Pgpool пропущен — пул не отвечает (иначе ожидание до таймаута PDO).');

            return self::SUCCESS;
        }

        $this->info('2) INSERT через Pgpool (ожидается ошибка при read-only standby / без primary)…');

        try {
            DB::table(self::TABLE)->insert([
                'token' => Str::uuid()->toString(),
                'created_at' => now()->toDateTimeString(),
            ]);
            $this->warn('INSERT прошёл — primary снова доступен или маршрутизация записи не к standby.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->line('   Ожидаемый отказ записи: '.$e->getMessage());

            return self::SUCCESS;
        }
    }
}
