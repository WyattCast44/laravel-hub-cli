<?php

namespace App\Commands;

use Exception;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use LaravelZero\Framework\Commands\Command;

class ComposeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'compose {script=app.yaml} {name?} {version?}';

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
     * The version to install
     * 
     * @var string
     */
    protected $version;

    /**
     * The composer-create command
     * 
     * @var string
     */
    protected $installCommand = "composer create-project laravel/laravel {NAME} {VERSION} --quiet";

    protected $composeKeyCommandMap = [
        'env'              => 'upcertEnvValues',
        'npm-packages'     => 'attemptToInstallNpmPackages',
        'npm-packages-dev' => 'attemptToInstallNpmDevPackages',
        'php-packages'     => 'attemptToInstallComposerPackages',
        'php-packages-dev' => 'attemptToInstallComposerDevPackages',
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this
            ->printBanner()
            ->determineRecipeToCompose()
            ->ensureScriptFileExists()
            ->loadRecipeFile()
            ->parseRecipeFileYaml()
            ->ensureRecipeIsNotEmpty()
            ->ensureProjectHasAName()
            ->ensureDirectoryDNE()
            ->determineVersionToIntall()
            ->buildUpComposerCreateCommand()
            ->runComposerCreateProject()
            ->changeDirectoryToProject()
            ->findHandlersForRemainingKeys()
            ->printEndBanner();
    }

    protected function printBanner()
    {
        $this->line(" _                               _   _    _       _     
| |                             | | | |  | |     | |    
| |     __ _ _ __ __ ___   _____| | | |__| |_   _| |__  
| |    / _` | '__/ _` \ \ / / _ \ | |  __  | | | | '_ \ 
| |___| (_| | | | (_| |\ V /  __/ | | |  | | |_| | |_) |
|______\__,_|_|  \__,_| \_/ \___|_| |_|  |_|\__,_|_.__/ ", 'fg=red');

        return $this;
    }

    public function determineRecipeToCompose()
    {
        $this->recipe = $this->argument('script');

        return $this;
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
                unset($this->contents['name']);
            }
        }

        if ($name == null) {
            throw new Exception('The project must have a name, either passed in via the CLI or set in the compose file.');
        }

        $this->projectName = $name;

        return $this;
    }

    public function ensureDirectoryDNE()
    {
        if (is_dir("./" . Str::slug($this->projectName))) {
            throw new Exception("A directory already exists in the install path, please delete directory or change app name.");
        }

        return $this;
    }

    public function determineVersionToIntall()
    {
        $version = null;

        if (array_key_exists('version', $this->contents)) {
            $version = $this->contents['version'];
            unset($this->contents['version']);
        }

        if ($this->argument('version')) {
            $version = $this->argument('version');
        }

        if ($version == null) {
            $version = "";
        }

        $this->version = $version;

        return $this;
    }

    public function buildUpComposerCreateCommand()
    {
        $command = trim(str_replace(
            "{NAME}",
            "\"" . Str::slug($this->projectName) . "\"",
            $this->installCommand
        ));

        if ($this->version != "") {
            $command = str_replace("{VERSION}", "\"" . $this->version . "\"", $command);
        } else {
            $command = str_replace("{VERSION}", "", $command);
        }

        $this->installCommand = trim($command);

        return $this;
    }

    public function runComposerCreateProject()
    {
        $this->line("");
        $this->info("===> Creating a fresh Laravel app");

        try {
            exec($this->installCommand);

            return $this;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function changeDirectoryToProject()
    {
        $path = "./" . Str::slug($this->projectName);

        chdir($path);

        return $this;
    }

    public function findHandlersForRemainingKeys()
    {
        foreach ($this->contents as $key => $value) {
            if (array_key_exists($key, $this->composeKeyCommandMap)) {
                $method = $this->composeKeyCommandMap[$key];
                $this->$method();
            }
        }

        return $this;
    }

    public function upcertEnvValues()
    {
        $this->line("");

        $this->info("===> Upserting env keys");

        if (array_key_exists('env', $this->contents)) {

            $keys = $this->contents['env'];

            foreach ($keys as $key => $value) {
                $this->call('env:set', [
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            unset($this->contents['env']);
        }

        return $this;
    }

    public function attemptToInstallComposerPackages($dev = false)
    {
        if (array_key_exists('php-packages', $this->contents) || array_key_exists('php-packages-dev', $this->contents)) {

            $this->line("");

            if (!$dev) {
                $this->info("===> Installing PHP packages");
                $packages = $this->contents['php-packages'];
            } else {
                $this->info("===> Installing PHP dev packages");
                $packages = $this->contents['php-packages-dev'];
            }

            foreach ($packages as $package) {

                if (gettype($package) == "array") {
                    //
                } else {
                    $this->call('php-package:install', [
                        'name' => $package,
                        '--dev' => $dev,
                    ]);
                }
            }
        }

        return $this;
    }

    public function attemptToInstallComposerDevPackages()
    {
        return tap($this, function ($c) {
            $c->attemptToInstallComposerPackages(true);
        });
    }

    public function attemptToInstallNpmPackages($dev = false)
    {
        if (array_key_exists('npm-packages', $this->contents) || array_key_exists('npm-packages-dev', $this->contents)) {

            $this->line("");

            if (!$dev) {
                $this->info("===> Installing NPM packages");
                $packages = $this->contents['npm-packages'];
            } else {
                $this->info("===> Installing NPM dev packages");
                $packages = $this->contents['npm-packages-dev'];
            }

            foreach ($packages as $package) {

                if (gettype($package) == "array") {
                    //
                } else {
                    $this->call('npm-package:install', [
                        'name' => $package,
                        '--dev' => $dev,
                    ]);
                }
            }
        }

        return $this;
    }

    public function attemptToInstallNpmDevPackages()
    {
        return tap($this, function ($c) {
            $c->attemptToInstallNpmPackages(true);
        });
    }

    public function printEndBanner()
    {
        $this->line("");

        $this->info("==> Application composed! Go build something amazing!");
    }
}
