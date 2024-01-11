<?php

namespace App\Commands;

use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;
use Throwable;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

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

    protected bool $hasPanels = false;

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

        $filamentPath = text(
            label: 'Filament path',
            default: '../filament/filament/packages/*',
            required: true,
        );

        try {
            spin(
                callback: function () use ($repo, $issueNumber) {
                    Process::run("git clone $repo issue-$issueNumber")->throw();
                },
                message: 'Cloning repository...',
            );

            $this->components->twoColumnDetail('Clone Repository', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $workingDirectory = getcwd() . "/issue-$issueNumber";
        chdir($workingDirectory);

        try {
            spin(
                callback: function () use ($workingDirectory, $filamentPath) {
                    $this->modifyComposer($workingDirectory, $filamentPath);
                },
                message: 'Modifying composer.json...',
            );

            $this->components->twoColumnDetail('Modify composer.json', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            spin(
                callback: function () {
                    Process::run("composer update")->throw();
                },
                message: 'Installing composer dependencies...',
            );

            $this->components->twoColumnDetail('Install composer dependencies', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            spin(
                callback: function () use ($branch) {
                    Process::run("git checkout $branch")->throw();
                },
                message: 'Checking out branch...',
            );

            $this->components->twoColumnDetail('Checkout branch', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {

            spin(
                callback: function () use ($workingDirectory) {
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

                    if ($this->hasPanels) {
                        try {
                            Process::run("php artisan make:filament-user --name=test --email=test@example.com --password=password")
                                ->throw();
                        } catch (Throwable) {}
                    }
                },
                message: 'Setting up repo...',
            );

            $this->components->twoColumnDetail('Setup repository', 'DONE');

            spin(
                callback: function () use ($workingDirectory) {
                    Process::run('npm install')->throw();
                    Process::run('npm run build')->throw();
                },
                message: 'Installing npm dependencies...',
            );

            $this->components->twoColumnDetail('Install npm dependencies', 'DONE');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

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

            Process::run("$editor .");
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

    private function modifyComposer(string $workingDirectory, string $filamentPath): void
    {
        $contents = json_decode(file_get_contents($workingDirectory . '/composer.json'), true);

        $packages = [
            'filament/filament',
            'filament/forms',
            'filament/tables',
            'filament/notifications',
            'filament/infolists',
            'filament/widgets',
            'filament/actions',
        ];

        foreach ($contents['require'] as $name => $version) {
            if (in_array($name, $packages, true)) {
                $contents['require'][$name] = '*';
            }
        }

        foreach ($contents['require-dev'] as $name => $version) {
            if (in_array($name, $packages, true)) {
                $contents['require-dev'][$name] = '*';
            }
        }

        $contents['repositories'] = [
            [
                'type' => 'path',
                'url' => $filamentPath,
            ],
        ];

        $contents['minimum-stability'] = 'dev';

        if (
            array_key_exists('filament/filament', $contents['require'])
            || array_key_exists('filament/filament', $contents['require-dev'])
        ) {
            $this->hasPanels = true;
        }

        file_put_contents($workingDirectory . '/composer.json', json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
