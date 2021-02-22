<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new 
                            {name} 
                            {--dev=false}
                            {--jet=false}
                            {--teams=false}
                            {--f|force=false}
                            ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'An proxy for composer create-project';

    protected $installCommand = 'composer create-project laravel/laravel {NAME} --remove-vcs --prefer-dist --no-progress';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        // Replace the name
        $command = str_replace('{NAME}', $name, $this->installCommand);

        // Install the application
        exec($command);
    }
}
