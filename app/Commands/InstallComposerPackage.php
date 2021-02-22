<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class InstallComposerPackage extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'php-package:install {name} {--dev=false} {--type=composer}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Install packages into your application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        exec("composer require {$this->argument('name')}");
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
