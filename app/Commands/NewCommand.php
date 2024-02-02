<?php

namespace App\Commands;

use App\Commands\Concerns\CanOpenEditor;
use App\Commands\Concerns\HasUtilities;
use App\Commands\Concerns\InteractsWithFiles;
use App\Commands\Concerns\InteractsWithGithub;
use App\Commands\Concerns\InteractsWithComposer;
use App\Commands\Concerns\InteractsWithNode;
use App\Commands\Concerns\InteractsWithLaravel;
use App\Setup;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    use CanOpenEditor;
    use HasUtilities;
    use InteractsWithComposer;
    use InteractsWithFiles;
    use InteractsWithGithub;
    use InteractsWithLaravel;
    use InteractsWithNode;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new {--F|force}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set up a new reproduction repo locally.';

    protected Setup $config;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->config = (new Setup())->configure();

        if ($this->option('force')) {
            Process::run("rm -rf " . $this->config->getWorkingDirectory());
        }

        $this
            ->cloneRepo()
            ->changeDirectory($this->config->getWorkingDirectory())
            ->modifyComposer()
            ->installComposerDependencies()
            ->installNpmDependencies()
            ->runNpmBuild()
            ->updateComposerDependencies()
            ->checkoutBranch()
            ->setupLaravel()
            ->openInEditor()
            ->changeDirectory($this->config->getInitialDirectory())
            ->getSuccessMessage();

        return self::SUCCESS;
    }
}
