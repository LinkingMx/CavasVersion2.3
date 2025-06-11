<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorImportJobs extends Command
{
    protected $signature = 'import:monitor';

    protected $description = 'Monitor import jobs progress and status';

    public function handle()
    {
        $this->info('🔍 Monitoring Import Jobs...');
        $this->line('');

        // Check queue status
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->info('📊 Queue Status:');
        $this->line("   Pending jobs: {$pendingJobs}");
        $this->line("   Failed jobs: {$failedJobs}");
        $this->line('');

        // Check recent products
        $recentProducts = \App\Models\Product::where('created_at', '>=', now()->subHour())->count();
        $totalProducts = \App\Models\Product::count();

        $this->info('📦 Products Status:');
        $this->line("   Recent imports (last hour): {$recentProducts}");
        $this->line("   Total products: {$totalProducts}");
        $this->line('');

        // Check import files directory
        $importFiles = glob(storage_path('app/private/imports/*'));
        $fileCount = count($importFiles);

        $this->info('📁 Import Files:');
        $this->line("   Files in imports directory: {$fileCount}");

        if ($fileCount > 0) {
            $this->line('   Files:');
            foreach ($importFiles as $file) {
                $filename = basename($file);
                $size = round(filesize($file) / 1024, 2);
                $this->line("   - {$filename} ({$size} KB)");
            }
        }

        $this->line('');

        // Memory usage
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $this->info('💾 Memory Usage:');
        $this->line("   Current: {$memoryUsage} MB");
        $this->line("   Peak: {$memoryPeak} MB");

        if ($pendingJobs > 0) {
            $this->warn("⚠️  There are {$pendingJobs} pending jobs. Make sure queue worker is running:");
            $this->line('   php artisan queue:work --timeout=3600');
        } else {
            $this->info('✅ No pending jobs');
        }
    }
}
