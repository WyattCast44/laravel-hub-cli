<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class InstallNpmPackage extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'npm-package:install {name} {--dev=false}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Install npm packages into your application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        $this->info("-> Installing {$name}");

        if (!$this->option('dev')) {
            exec("npm install {$name} --silent");
        } else {
            exec("npm install {$name} --silent --save-dev");
        }
    }
}
