<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class sendNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queue worker to send notifications in background every minute';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting loop...');

        $start = time();
        while ((time() - $start) < 60) { // Run for 1 minute
            // Run the queue worker once
            Artisan::call('queue:work --once --stop-when-empty');
            $this->info('Queue worker run at ' . now());

            sleep(5); // Wait 5 seconds
        }

        $this->info('Finished 1-minute loop');
    }
}
