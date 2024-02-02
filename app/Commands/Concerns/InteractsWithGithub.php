<?php

namespace App\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;
use function Laravel\Prompts\spin;

trait InteractsWithGithub
{
    public function cloneRepo(): static
    {
        $repo = $this->config->getRepo();
        $issueNumber = $this->config->getIssueNumber();

        try {
            spin(
                callback: fn () => Process::run("git clone $repo issue-$issueNumber")->throw(),
                message: 'Cloning repository...',
            );

            $this->components->twoColumnDetail('Clone Repository', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }

    public function checkoutBranch(): static
    {
        $branch = $this->config->getBranch();

        try {
            spin(
                callback: fn () => Process::run("git checkout $branch")->throw(),
                message: 'Checking out branch...',
            );

            $this->components->twoColumnDetail('Checkout branch', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            exit();
        }

        return $this;
    }
}
