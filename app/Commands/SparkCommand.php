<?php

namespace App\Commands;

use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class SparkCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'spark';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set up a new reproduction repo locally.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $repo = text(
            label: 'Repository',
            required: true,
            validate: fn (string $repo): ?string => match (true) {
                ! str_contains($repo, 'github') => 'The repository must be a valid GitHub repository.',
                default => null,
            },
        );

        $issueNumber = text(
            label: 'Issue number',
            required: true,
            validate: fn (string $issueNumber): ?string => match (true) {
                ! filter_var($issueNumber, FILTER_VALIDATE_INT) => 'The issue number must be a valid integer.',
                default => null,
            },
        );

        $branch = text(
            label: 'Branch',
            default: 'main',
        );

        try {
            $this->info('Cloning repository...');
            Process::run("git clone $repo issue-$issueNumber")->throw();
            $this->info('Repository cloned.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $workingDirectory = getcwd() . "/issue-$issueNumber";
        chdir($workingDirectory);

        try {
            $this->info('Installing dependencies...');
            Process::run("composer install")->throw();
            $this->info('Dependencies installed.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $this->info('Checking out branch...');
            Process::run("git checkout $branch")->throw();
            $this->info('Branch checked out.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $composer = json_decode(file_get_contents($workingDirectory . '/composer.json'), true);
        $hasPanels = array_key_exists('filament/filament', $composer['require'])
            || array_key_exists('filament/filament', $composer['require-dev']);

        try {
            $this->info('Setting up repo...');
            Process::run("cp .env.example .env")->throw();
            Process::run("php artisan key:generate")->throw();
            Process::run("touch database/database.sqlite")->throw();

            $this->replaceInFile(
                $workingDirectory . '/.env',
                [
                    'DB_CONNECTION=mysql' => 'DB_CONNECTION=sqlite',
                    'DB_DATABASE=' => '#DB_DATABASE=',
                ]);

            Process::run("php artisan migrate")->throw();
            Process::run("php artisan db:seed")->throw();

            if ($hasPanels) {
                Process::run("php artisan make:filament-user --name=test --email=test@example.com --password=password")
                    ->throw();
            }

            Process::run('npm install')->throw();
            Process::run('npm run build')->throw();

            $openInEditor = confirm(
                label: 'Open in editor?',
                default: true,
            );

            if ($openInEditor) {
                $editor = select(
                    label: 'Editor',
                    options: [
                        'VS Code',
                        'PHPStorm (phpstorm)',
                        'PHPStorm Alt (pstorm)',
                    ],
                    default: 'VS Code',
                );

                $editor = match ($editor) {
                    'VS Code' => 'code',
                    'PHPStorm (phpstorm)' => 'phpstorm',
                    'PHPStorm Alt (pstorm)' => 'pstorm',
                };

                Process::run("$editor .")->throw();
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Repro is ready 🦒');
        $this->newLine();
        $this->comment("1. cd $workingDirectory");
        $this->comment('2. php artisan serve');

        return self::SUCCESS;
    }

    private function replaceInFile(string $file, array $replacements): void
    {
        $contents = file_get_contents($file);

        file_put_contents(
            $file,
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $contents
            )
        );
    }
}
