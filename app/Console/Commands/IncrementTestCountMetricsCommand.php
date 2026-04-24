<?php

namespace App\Console\Commands;

use App\Services\Prometheus\Metrics;
use Illuminate\Console\Command;

class IncrementTestCountMetricsCommand extends Command
{
    protected $signature = 'metrics:test-count
                            {--times=1 : Сколько раз увеличить счётчик}';

    protected $description = 'Увеличить Prometheus-счётчик test_count (для проверки /metrics)';

    public function handle(Metrics $metrics): int
    {
        $times = max(1, (int) $this->option('times'));

        $counter = $metrics->counter('test_count', 'Test counter incremented via artisan metrics:test-count');

        for ($i = 0; $i < $times; $i++) {
            $counter->inc();
        }

        $this->info("Счётчик test_count увеличен на {$times}.");

        return self::SUCCESS;
    }
}
