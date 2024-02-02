<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;
use function Laravel\Prompts\spin;

trait InteractsWithLaravel
{
    public function setupLaravel(): static
    {
        $dir = $this->config->getWorkingDirectory();

        try {
            spin(
                callback: function () use ($dir) {
                    Process::pipe([
                        "cp .env.example .env",
                        "php artisan key:generate",
                        "touch database/database.sqlite"
                    ])->throw();

                    $this->replaceInFile(
                        $dir . '/.env',
                        [
                            'DB_CONNECTION=mysql' => 'DB_CONNECTION=sqlite',
                            'DB_DATABASE=' => '#DB_DATABASE=',
                        ]);

                    Process::run("php artisan migrate")->throw();
                    Process::timeout(360)->run("php artisan db:seed")->throw();

                    if ($this->hasPanels) {
                        try {
                            Process::run("php artisan make:filament-user --name=test --email=test@example.com --password=password")
                                ->throw();
                        } catch (Throwable $e) {
                            $this->error($e->getMessage());
                        }
                    }
                },
                message: 'Setting up Laravel...',
            );

            $this->components->twoColumnDetail('Setup Laravel', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }
}
