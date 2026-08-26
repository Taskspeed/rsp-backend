<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckSmsPorts extends Command
{
    protected $signature   = 'sms:check-ports';
    protected $description = 'Check which SMS gateway ports are alive';

    public function handle(): void
    {
        $baseUrl = config('app.sms_api_url');
        $apiUser = config('app.sms_api_user');
        $apiPass = config('app.sms_api_pass');

        $this->info("Checking SMS gateway ports on: {$baseUrl}");
        $this->newLine();

        $rows = [];

        // foreach (range(1, 4) as $port) {
        foreach ([1,2,3,4] as $port) {
            // Send a dummy number — gateway will reject it but at least responds
            $url = "{$baseUrl}"
                . "?1500101=account={$apiUser}"
                . "&password={$apiPass}"
                . "&port={$port}"
                . "&destination=09153994591"
              . "&content=Test+from+port+{$port}";

            $start = microtime(true);

            try {
                $response = Http::timeout(60)->get($url);
                $ms       = round((microtime(true) - $start) * 1000);
                $status   = $response->status();
                $body     = substr($response->body(), 0, 80); // trim long responses

                $rows[] = [
                    $port,
                    '✅ REACHABLE',
                    "{$ms}ms",
                    "HTTP {$status}",
                    $body,
                ];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $ms     = round((microtime(true) - $start) * 1000);
                $rows[] = [
                    $port,
                    '❌ TIMEOUT/UNREACHABLE',
                    "{$ms}ms",
                    '-',
                    $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $rows[] = [
                    $port,
                    '⚠️  ERROR',
                    '-',
                    '-',
                    $e->getMessage(),
                ];
            }
        }

        $this->table(
            ['Port', 'Status', 'Response Time', 'HTTP Code', 'Response/Error'],
            $rows
        );
    }
    //   protected $signature   = 'sms:check-ports {--port=*} {--times=10} {--delay=5} {--to=09153994591}';
    // protected $description = 'Check SMS gateway port stability over multiple attempts';

    // public function handle(): void
    // {
    //     $baseUrl = config('app.sms_api_url');
    //     $apiUser = config('app.sms_api_user');
    //     $apiPass = config('app.sms_api_pass');

    //     $ports = $this->option('port') ?: [1, 2, 3, 4];
    //     $times = (int) $this->option('times');
    //     $delay = (int) $this->option('delay');
    //     $to    = $this->option('to');

    //     $this->info("Checking SMS gateway stability on: {$baseUrl}");
    //     $this->info("Ports: " . implode(',', $ports) . " | Attempts each: {$times}");
    //     $this->newLine();

    //     $stats = [];

    //     foreach ($ports as $port) {
    //         $stats[$port] = [
    //             'success'   => 0,
    //             'fail'      => 0,
    //             'timeout'   => 0,
    //             'times_ms'  => [],
    //         ];

    //         for ($i = 1; $i <= $times; $i++) {
    //             $url = "{$baseUrl}"
    //                 . "?1500101=account={$apiUser}"
    //                 . "&password={$apiPass}"
    //                 . "&port={$port}"
    //                 . "&destination={$to}"
    //                 . "&content=Stability+test+P{$port}+#{$i}+" . now()->format('His');

    //             $start = microtime(true);

    //             try {
    //                 $response = Http::timeout(60)->get($url);
    //                 $ms   = round((microtime(true) - $start) * 1000);
    //                 $body = $response->body();

    //                 $isSuccess = $response->ok() && str_contains($body, 'Commit successfully');

    //                 if ($isSuccess) {
    //                     $stats[$port]['success']++;
    //                 } else {
    //                     $stats[$port]['fail']++;
    //                 }

    //                 $stats[$port]['times_ms'][] = $ms;

    //                 $this->line("Port {$port} | Try {$i}/{$times} | {$ms}ms | " . ($isSuccess ? '✅ OK' : '⚠️ FAIL') . " | " . trim(str_replace(["\r", "\n"], ' ', $body)));

    //             } catch (\Illuminate\Http\Client\ConnectionException $e) {
    //                 $stats[$port]['timeout']++;
    //                 $this->line("Port {$port} | Try {$i}/{$times} | ❌ TIMEOUT | " . $e->getMessage());
    //             } catch (\Exception $e) {
    //                 $stats[$port]['fail']++;
    //                 $this->line("Port {$port} | Try {$i}/{$times} | ⚠️ ERROR | " . $e->getMessage());
    //             }

    //             if ($i < $times) {
    //                 sleep($delay);
    //             }
    //         }
    //     }

    //     $this->newLine();
    //     $this->info('=== Stability Summary ===');

    //     $rows = [];
    //     foreach ($stats as $port => $s) {
    //         $total   = $s['success'] + $s['fail'] + $s['timeout'];
    //         $avgMs   = count($s['times_ms']) ? round(array_sum($s['times_ms']) / count($s['times_ms'])) : 0;
    //         $minMs   = count($s['times_ms']) ? min($s['times_ms']) : 0;
    //         $maxMs   = count($s['times_ms']) ? max($s['times_ms']) : 0;
    //         $rate    = $total ? round(($s['success'] / $total) * 100, 1) : 0;

    //         $rows[] = [
    //             $port,
    //             "{$s['success']}/{$total}",
    //             "{$rate}%",
    //             $s['fail'],
    //             $s['timeout'],
    //             "{$avgMs}ms",
    //             "{$minMs}-{$maxMs}ms",
    //         ];
    //     }

    //     $this->table(
    //         ['Port', 'Success', 'Success Rate', 'Failed', 'Timeout', 'Avg Time', 'Min-Max Time'],
    //         $rows
    //     );
    // }
}