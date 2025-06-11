<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanImportFiles extends Command
{
    protected $signature = 'import:clean {--days=7 : Files older than this many days will be deleted}';

    protected $description = 'Clean old import files from storage';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("🧹 Cleaning import files older than {$days} days...");

        $importDir = storage_path('app/private/imports');
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        $files = glob($importDir.'/*');
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                $size = filesize($file);
                $totalSize += $size;

                if (unlink($file)) {
                    $deletedCount++;
                    $this->line('   Deleted: '.basename($file).' ('.round($size / 1024, 2).' KB)');
                }
            }
        }

        if ($deletedCount > 0) {
            $totalSizeMB = round($totalSize / 1024 / 1024, 2);
            $this->info("✅ Deleted {$deletedCount} files, freed {$totalSizeMB} MB");
        } else {
            $this->info('✅ No old files found to delete');
        }
    }
}
