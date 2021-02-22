<?php

namespace App\Commands;

use Exception;
use Symfony\Component\Yaml\Yaml;
use LaravelZero\Framework\Commands\Command;

class ComposeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'compose {script=app.yaml} {name?}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Compose your application with the given compose file.';

    /**
     * The compose file that we will attempt to compose
     *
     * @var string
     */
    protected $recipe;

    /**
     * The raw string contents of the recipe file
     * 
     * @var string
     */
    protected $rawRecipe;

    /**
     * The parsed recipe file
     * 
     * @var array
     */
    protected $contents;

    /**
     * The project name
     * 
     * @var string
     */
    protected $projectName;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->recipe = $this->argument('script');

        $this
            ->ensureScriptFileExists()
            ->loadRecipeFile()
            ->parseRecipeFileYaml()
            ->ensureRecipeIsNotEmpty()
            ->ensureProjectHasAName();

        dd($this->projectName);
    }

    public function ensureScriptFileExists()
    {
        if (file_exists('./' . $this->recipe)) {
            return $this;
        }

        throw new Exception('Unable to find the given compose file: ' . $this->recipe);
    }

    public function loadRecipeFile()
    {
        try {
            $this->rawRecipe = file_get_contents($this->recipe);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function parseRecipeFileYaml()
    {
        try {
            $this->contents = Yaml::parse($this->rawRecipe);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function ensureRecipeIsNotEmpty()
    {
        if (empty($this->contents)) {
            throw new Exception("Compose file ({$this->recipe}) cannot be empty.");
        }

        return $this;
    }

    public function ensureProjectHasAName()
    {
        $name = $this->argument('name');

        if ($name) {
            $this->projectName = $name;
        } else {
            if (array_key_exists('name', $this->contents)) {
                $name = $this->contents['name'];
            }
        }

        if ($name == null) {
            throw new Exception('The project must have a name, either passed in via the CLI or set in the compose file.');
        }

        $this->projectName = $name;

        return $this;
    }
}
