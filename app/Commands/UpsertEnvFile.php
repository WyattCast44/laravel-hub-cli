<?php

namespace App\Commands;

use Exception;
use SplFileObject;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class UpsertEnvFile extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:set 
                                {key : The env key that you want to set } 
                                {value : The value to set the key to }
                                {--dont-create : If the key does not exist, do not create it }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update or set an env file value';

    /**
     * The key to update
     */
    protected $keyName;

    /**
     * The value to set the key too
     */
    protected $keyValue;

    /**
     * Should non-existant keys be created
     */
    protected $createNonExistingKeys = true;

    /**
     * The path to the .env file
     */
    protected $path;

    /**
     * Was the key ever found in the file
     */
    protected $found = false;


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->keyName = $this->argument('key');
        $this->keyValue = $this->argument('value');
        $this->createNonExistingKeys = ($this->option('dont-create')) ? false : true;

        $this
            ->ensureEnvFileExists()
            ->checkIfEnvFileHasKey()
            ->updateExistingKeysIfPresent()
            ->appendNewKeysIfNotPresent();

        return 0;
    }

    protected function ensureEnvFileExists()
    {
        $path = '.env';

        if (!file_exists($path)) {
            throw new Exception("Env file does not exist, cannot update keys.");
        }

        $this->path = $path;

        return $this;
    }

    public function checkIfEnvFileHasKey()
    {

        if (Str::contains(file_get_contents($this->path), $this->keyName)) {
            $this->found = true;
        } else {
            $this->found = false;
        }

        return $this;
    }

    protected function updateExistingKeysIfPresent()
    {
        if (!$this->found) {
            return $this;
        }

        $newFile = "";

        $handle = fopen($this->path, "r+");

        if ($handle) {
            while (!feof($handle)) // Loop til end of file.
            {
                $buffer = fgets($handle); // Read a line.

                if (trim($buffer) == "") {
                    $newFile = $newFile;
                }

                $parts = explode("=", $buffer);

                if ($parts[0] <> $this->keyName) {
                    // Blank line, keep it to keep default spacing
                    $newFile = $newFile . $buffer;
                } else {
                    // Key found, overwrite it
                    if (trim($parts[0]) == $this->keyName) {
                        if (preg_match('/"/', $parts[1]) || strpos($this->keyValue, " ")) {
                            $buffer = "{$parts[0]}=" . "\"" . $this->keyValue . "\"\n";
                            $newFile = $newFile . $buffer;
                        } else {
                            $buffer = "{$parts[0]}=" . $this->keyValue . "\n";
                            $newFile = $newFile . $buffer;
                        }
                    }
                }
            }
            fclose($handle); // Close the file.
        }

        file_put_contents($this->path, $newFile);

        $this->line("-> Updated {$this->keyName} to {$this->keyValue}");

        return $this;
    }

    public function appendNewKeysIfNotPresent()
    {
        if ($this->found) {
            return $this;
        }

        if (strpos($this->keyValue, " ")) {
            $str = "{$this->keyName}=" . "\"" . $this->keyValue . "\"\n";
        } else {
            $str = "{$this->keyName}=" . $this->keyValue . "\n";
        }

        file_put_contents(
            $this->path,
            "\n{$str}",
            FILE_APPEND
        );

        $this->line("-> Upserted {$this->keyName} to {$this->keyValue}");
    }
}
