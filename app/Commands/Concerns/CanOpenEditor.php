<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;

trait CanOpenEditor
{
    public function openInEditor(): static
    {
        if ($this->config->shouldOpenEditor()) {
            try {
                if ($this->config->getEditor() === 'phpstorm') {
                    Process::run('open -na "PhpStorm.app" --args .')->throw();
                } else {
                    Process::run($this->config->getEditor() . ' .')->throw();
                }
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        return $this;
    }
}
