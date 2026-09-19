<?php

namespace App\Console\Commands;

use App\Services\GraphMailService;
use Illuminate\Console\Command;
use Throwable;

class FetchMailboxMessages extends Command
{
    protected $signature = 'mailbox:fetch';
    protected $description = 'Synchronize the shared mailbox inbox';

    public function handle(GraphMailService $graph): int
    {
        if (!config('mailbox.enabled')) {
            $this->error('Client Mailbox is disabled.');
            return self::FAILURE;
        }
        try {
            $created = $graph->fetchNewMessages();
            $this->info(count($created).' new message(s) imported.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error('Mailbox import failed. Check the application logs and Microsoft configuration.');
            return self::FAILURE;
        }
    }
}
