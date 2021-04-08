<?php

namespace App\Commands;

use Exception;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;
use LaravelZero\Framework\Commands\Command;

class ComposeCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'compose {script=app.yaml} {--force}';

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
     * Flag if we are setting up git
     */
    protected $usingGit = false;

    /**
     * The composer-create command
     * 
     * @var string
     */
    protected $installCommand 
        = "composer create-project laravel/laravel {NAME} {VERSION} --remove-vcs --prefer-dist --no-progress --quiet";

    protected $composeKeyCommandMap = [
        // Misc
        'env'              => 'upcertEnvValues',
        'touch'            => 'attemptToCreateFiles',
        'mkdir'            => 'attemptToCreateDirectories',
        'console'          => 'attemptToRunConsoleCommand',
        // FE Deps
        'npm-packages'     => 'attemptToInstallNpmPackages',
        'npm-packages-dev' => 'attemptToInstallNpmDevPackages',
        // BE Deps
        'php-packages'     => 'attemptToInstallComposerPackages',
        'php-packages-dev' => 'attemptToInstallComposerDevPackages',
        // laravel
        'artisan'          => 'attemptToRunArtisanCommand',
        'blueprint'        => 'attemptToInstallBlueprint',
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
            ->determineIfUsingGit()
            ->attemptToInitGit()
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
        if (array_key_exists('name', $this->contents) && $this->contents['name'] != null) {
            
            $this->projectName = $this->contents['name'];
        
            unset($this->contents['name']);

        } else {
            throw new Exception('The recipe must contain a non-null name.');
        }

        return $this;
    }

    public function ensureDirectoryDNE()
    {
        $path = "./" . Str::slug($this->projectName);
        
        if (is_dir($path)) {
            if ($this->option('force')) {
                // Directory already exists, need to delete it.
                exec("rm -rf " . "./" . Str::slug($this->projectName));
            } else {
                // Directory already exists, but --force is false.
                throw new Exception(
                    "A directory already exists at the install path. To overwrite the directory use --force, or rename the application."
                );
            }
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

    public function determineIfUsingGit()
    {
        if (array_key_exists('git', $this->contents) && $this->contents['git']) {
            $this->usingGit = true;
            unset($this->contents['git']);
        }

        return $this;
    }

    public function attemptToInitGit()
    {
        if ($this->usingGit) {

            $this->line("");
            $this->info("===> Creating a new Git repository");

            exec('git init --quiet --initial-branch=main');
    
            // Commit initial progress
            (new Process(['git add .']))
                ->disableOutput()
                ->run();

            exec('git commit -q -m "Creating a fresh Laravel app"');
        }

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

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Upserting env values"');
            }
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

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Adding PHP dependencies"');
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

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Adding NPM packages"');
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

    public function attemptToCreateFiles()
    {
        if (array_key_exists('touch', $this->contents)) {

            $this->line("");
            $this->info("===> Creating files");

            $files = $this->contents['touch'];

            foreach ($files as $file) {

                if (gettype($file) == "array") {
                    //
                } else {
                    if (!file_exists(dirname($file))) {
                        mkdir(dirname($file));
                    }
                    if (!file_exists($file)) {
                        touch($file);

                        if (pathinfo($file, PATHINFO_EXTENSION) === "php") {
                            file_put_contents($file, "<?php " . PHP_EOL . PHP_EOL . '// @TODO' . PHP_EOL);
                        }
                    }

                    $this->line("-> Created File: {$file}");
                }
            }

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Creating files"');
            }

        }

        return $this;
    }

    public function attemptToCreateDirectories()
    {
        if (array_key_exists('mkdir', $this->contents)) {

            $this->line("");
            $this->info("===> Creating directories");

            $dirs = $this->contents['mkdir'];

            foreach ($dirs as $dir) {

                if (gettype($dir) == "array") {
                    //
                } else {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }

                    $this->line("-> Created Directory: {$dir}");
                }
            }

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Creating directories"');
            }
        }

        return $this;
    }

    public function attemptToRunConsoleCommand()
    {
        if (array_key_exists('console', $this->contents)) {

            $this->line("");
            $this->info("===> Running console commands");

            $commands = $this->contents['console'];

            foreach ($commands as $command) {

                if (gettype($command) == "array") {
                    //
                } else {
                    exec("{$command}", $output);

                    $this->line("-> Ran command: {$command}");
                }
            }

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('Running console commands"');
            }
        }

        return $this;
    }

    public function attemptToRunArtisanCommand()
    {
        if (array_key_exists('artisan', $this->contents)) {

            $this->line("");
            $this->info("===> Running artisan commands");

            $commands = $this->contents['artisan'];

            foreach ($commands as $command) {

                if (gettype($command) == "array") {
                    //
                } else {
                    exec("php artisan {$command}");

                    $this->line("-> Ran command: {$command}");
                }
            }

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Running artisan commands"');
            }
        }

        return $this;
    }

    public function attemptToInstallBlueprint()
    {
        if (array_key_exists('blueprint', $this->contents)) {

            $this->line("");
            $this->info("===> Installing Blueprint and creating draft file");

            // Install packages
            $this->call('php-package:install', [
                'name' => 'laravel-shift/blueprint',
                '--dev' => true,
            ]);

            $this->call('php-package:install', [
                'name' => 'jasonmccreary/laravel-test-assertions',
                '--dev' => true,
            ]);            

            // Create draft file
            touch('draft.yaml');
            
            // Write contents to draft file
            file_put_contents('draft.yaml', Yaml::dump($this->contents['blueprint'], 20, 2));

            if ($this->usingGit) {
                (new Process(['git add .']))
                    ->disableOutput()
                    ->run();

                exec('git commit -q -m "Creating Laravel Blueprint draft file"');
            }
        }

        return $this;
    }

    public function printEndBanner()
    {
        $this->line("");

        $this->info("==> Application composed! Go build something amazing!");
    }
}
