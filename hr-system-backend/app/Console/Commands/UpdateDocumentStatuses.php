<?php

namespace App\Console\Commands;

use App\Services\DocumentService;
use Illuminate\Console\Command;

class UpdateDocumentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:update-statuses';

    /**
     * The console command description.
     */
    protected $description = 'Update document statuses based on expiry dates (active -> expiring -> expired)';

    public function __construct(private DocumentService $documentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating document expiry statuses...');

        $counts = $this->documentService->updateExpiryStatuses();

        $this->info("Documents marked as expiring: {$counts['expiring']}");
        $this->info("Documents marked as expired: {$counts['expired']}");
        $this->info('Document status update completed.');

        return self::SUCCESS;
    }
}
