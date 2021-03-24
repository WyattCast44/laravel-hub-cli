<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class InstallComposerPackage extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'php-package:install {name} {--dev}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Install composer packages into your application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        $this->line("-> Installing {$name}");

        if (!$this->option('dev')) {
            exec("composer require {$name} --quiet");
        } else {
            exec("composer require {$name} --quiet --dev");
        }
    }
}
