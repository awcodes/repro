<?php

namespace App\Commands\Concerns;

trait HasUtilities
{
    public function changeDirectory(string $directory): static
    {
        chdir($directory);

        return $this;
    }

    public function getSuccessMessage(): static
    {
        $path = ltrim(str_replace($this->config->getInitialDirectory(), '', $this->config->getWorkingDirectory()), '/');

        $this->newLine();
        $this->info('Reproduction repo is ready ✨');
        $this->newLine();
        $this->comment("1. cd " . $path);
        $this->comment('2. php artisan serve');

        return $this;
    }
}
