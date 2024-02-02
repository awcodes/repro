<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;
use function Laravel\Prompts\spin;

trait InteractsWithNode
{
    public function installNpmDependencies(): static
    {
        try {
            spin(
                callback: fn () => Process::run('npm install')->throw(),
                message: 'Installing npm dependencies...',
            );

            $this->components->twoColumnDetail('Install npm dependencies', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }

    public function runNpmBuild(): static
    {
        try {
            spin(
                callback: fn () => Process::run('npm run build')->throw(),
                message: 'Building npm...',
            );

            $this->components->twoColumnDetail('Npm build', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }
}
