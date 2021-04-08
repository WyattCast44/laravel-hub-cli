<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new {name}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new Laravel application using composer';

    /**
     * The basic install command, just need to 
     * replace the name
     * 
     * @var string
     */
    protected $installCommand = 
        'composer create-project laravel/laravel {NAME} --remove-vcs --prefer-dist --no-progress --quiet';

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
