<?php

namespace App\Services\Prometheus;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\PDO;

class Metrics
{
    private CollectorRegistry $registry;

    public function __construct()
    {
        // PDO-адаптер: персистентное хранение метрик в PostgreSQL между процессами PHP-FPM.
        try {
            $pdo = DB::connection()->getPdo();
            $adapter = new PDO($pdo);
        } catch (\Throwable $e) {
            Log::warning('Failed to initialize PDO adapter for Prometheus metrics, using InMemory', [
                'error' => $e->getMessage(),
            ]);
            $adapter = new \Prometheus\Storage\InMemory();
        }

        $this->registry = new CollectorRegistry($adapter);
    }

    public function counter(string $name, string $help, array $labels = [])
    {
        return $this->registry->getOrRegisterCounter('laravel', $name, $help, $labels);
    }

    public function gauge(string $name, string $help, array $labels = [])
    {
        return $this->registry->getOrRegisterGauge('laravel', $name, $help, $labels);
    }

    public function histogram(string $name, string $help, array $labels = [], ?array $buckets = null)
    {
        return $this->registry->getOrRegisterHistogram('laravel', $name, $help, $labels, $buckets);
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat();
        $metrics = $this->registry->getMetricFamilySamples();

        return $renderer->render($metrics);
    }
}
