<?php

namespace App\Console\Commands;

use App\Jobs\ProductImportJob;
use App\Models\User;
use Illuminate\Console\Command;

class TestImportJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:import-job {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the ProductImportJob';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return;
        }

        $user = User::first();
        if (! $user) {
            $this->error('No users found in database');

            return;
        }

        $this->info('Testing ProductImportJob...');
        $this->info("File: {$filePath}");
        $this->info("User: {$user->email}");

        try {
            // Test 1: Execute job directly
            $this->info("\n--- Test 1: Direct execution ---");
            $job = new ProductImportJob($filePath, $user);
            $job->handle();
            $this->info('✅ Direct execution completed');

            // Test 2: Dispatch to queue
            $this->info("\n--- Test 2: Queue dispatch ---");
            ProductImportJob::dispatch($filePath, $user);
            $this->info('✅ Job dispatched to queue');

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
        }

        // Check results
        $productCount = \App\Models\Product::count();
        $this->info("\n📊 Products in database: {$productCount}");
    }
}
