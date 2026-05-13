<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\Controllers\RecurringInvoiceController;
use App\Models\RecurringInvoice;
use App\Models\Tenant;
use Illuminate\Console\Command;

class ProcessRecurringInvoices extends Command
{
    protected $signature   = 'recurring:process';
    protected $description = 'Generate invoices for all due recurring invoice schedules';

    public function handle(): int
    {
        $dueRecurring = RecurringInvoice::due()->with(['client', 'tenant'])->get();

        if ($dueRecurring->isEmpty()) {
            $this->info('No recurring invoices are due.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueRecurring->count()} due recurring invoice(s). Processing...");

        $controller = new RecurringInvoiceController();
        $generated  = 0;
        $failed     = 0;

        foreach ($dueRecurring as $recurring) {
            try {
                $tenant  = $recurring->tenant;
                $invoice = $controller->generateInvoice($recurring, $tenant);

                // Advance schedule
                $recurring->update([
                    'last_run_at' => now(),
                    'next_run_at' => $recurring->calculateNextRunAt(),
                ]);

                $this->line("  ✅ Generated Invoice #{$invoice->number} for tenant #{$recurring->tenant_id}");
                $generated++;

            } catch (\Throwable $e) {
                $this->error("  ❌ Failed for recurring #{$recurring->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. Generated: {$generated} | Failed: {$failed}");
        return self::SUCCESS;
    }
}
