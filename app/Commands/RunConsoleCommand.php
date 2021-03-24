<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class RunConsoleCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run-console-command {commandName} {commandArgs}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run a given console command with the given args';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            exec("{$this->argument('commandName')} {$this->argument('commandArgs')}");
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
