<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class QueueWorkerTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10;
    protected $testKey;

    public function __construct($testKey)
    {
        $this->testKey = $testKey;
    }

    public function handle()
    {
        // Mark as processed
        Cache::put($this->testKey, 'processed', 10);
    }
}
